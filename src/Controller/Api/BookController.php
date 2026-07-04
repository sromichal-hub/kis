<?php

namespace App\Controller\Api;

use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/books')]
class BookController
{
    public function __construct(private EntityManagerInterface $em, private BookRepository $repo)
    {
    }

    #[Route('', name: 'book_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true);

        $serial = $data['serialNumber'] ?? null;
        $title = $data['title'] ?? null;
        $author = $data['author'] ?? null;

        // basic validation
        if (!preg_match('/^\\d{6}$/', (string)$serial)) {
            return new JsonResponse(['error' => 'serialNumber must be a 6-digit string'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($title) || empty($author)) {
            return new JsonResponse(['error' => 'title and author are required'], Response::HTTP_BAD_REQUEST);
        }

        // uniqueness check
        $existing = $this->repo->findOneBy(['serialNumber' => $serial]);
        if ($existing) {
            return new JsonResponse(['error' => 'Book with this serialNumber already exists'], Response::HTTP_CONFLICT);
        }

        $book = new Book();
        $book->setSerialNumber($serial)
            ->setTitle($title)
            ->setAuthor($author);

        $this->em->persist($book);
        $this->em->flush();

        return new JsonResponse($book->toArray(), Response::HTTP_CREATED);
    }

    #[Route('', name: 'book_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $books = $this->repo->findAll();
        $data = array_map(fn(Book $b) => $b->toArray(), $books);

        return new JsonResponse($data);
    }

    #[Route('/{id}', name: 'book_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $book = $this->repo->find($id);
        if (!$book) {
            return new JsonResponse(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($book);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Update state: borrow or return
     * POST body for borrow: {"action":"borrow","cardNumber":"123456"}
     * for return: {"action":"return"}
     */
    #[Route('/{id}/state', name: 'book_state', methods: ['PATCH'])]
    public function state(int $id, Request $request): JsonResponse
    {
        $book = $this->repo->find($id);
        if (!$book) {
            return new JsonResponse(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent() ?: '{}', true);
        $action = $data['action'] ?? null;

        if ($action === 'borrow') {
            $card = $data['cardNumber'] ?? null;
            if (!preg_match('/^\\d{6}$/', (string)$card)) {
                return new JsonResponse(['error' => 'cardNumber must be a 6-digit string'], Response::HTTP_BAD_REQUEST);
            }
            if ($book->isBorrowed()) {
                return new JsonResponse(['error' => 'Book is already borrowed'], Response::HTTP_CONFLICT);
            }
            $book->setIsBorrowed(true);
            $book->setBorrowedAt(new \DateTimeImmutable());
            $book->setBorrowedByCardNumber($card);

            $this->em->flush();

            return new JsonResponse($book->toArray());
        }

        if ($action === 'return') {
            if (!$book->isBorrowed()) {
                return new JsonResponse(['error' => 'Book is not borrowed'], Response::HTTP_CONFLICT);
            }
            $book->setIsBorrowed(false);
            $book->setBorrowedAt(null);
            $book->setBorrowedByCardNumber(null);

            $this->em->flush();

            return new JsonResponse($book->toArray());
        }

        return new JsonResponse(['error' => 'Invalid action'], Response::HTTP_BAD_REQUEST);
    }
}


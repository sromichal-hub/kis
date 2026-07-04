<?php

namespace App\Tests;

use App\Entity\Book;
use App\Controller\Api\BookController;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class BookApiTest extends TestCase
{
    private EntityManagerInterface $em;
    private BookRepository $repo;
    private BookController $controller;

    protected function setUp(): void
    {
        // Create mock EntityManager and BookRepository
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repo = $this->createMock(BookRepository::class);

        // Create controller with mocked dependencies
        $this->controller = new BookController($this->em, $this->repo);
    }

    public function testCreateBook(): void
    {
        // Mock repo to return null (no existing book with this serial)
        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['serialNumber' => '111111'])
            ->willReturn(null);

        // Mock em to accept persist/flush
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'serialNumber' => '111111',
                'title' => 'Test Book',
                'author' => 'Author Name'
            ])
        );
        $response = $this->controller->create($request);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('serialNumber', $data);
        $this->assertEquals('111111', $data['serialNumber']);
        $this->assertEquals('Test Book', $data['title']);
        $this->assertEquals('Author Name', $data['author']);
    }

    public function testCreateBookWithInvalidSerialNumber(): void
    {
        // Should not even call repo for invalid input
        $this->repo->expects($this->never())->method('findOneBy');
        $this->em->expects($this->never())->method('persist');

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'serialNumber' => '12345',  // Invalid: not 6 digits
                'title' => 'Book',
                'author' => 'Author'
            ])
        );
        $response = $this->controller->create($request);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('6-digit', $data['error']);
    }

    public function testCreateBookWithDuplicateSerialNumber(): void
    {
        // Mock repo to find existing book
        $existingBook = new Book();
        $existingBook->setSerialNumber('777777');
        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['serialNumber' => '777777'])
            ->willReturn($existingBook);

        // Should not persist due to duplicate
        $this->em->expects($this->never())->method('persist');

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'serialNumber' => '777777',
                'title' => 'Book',
                'author' => 'Author'
            ])
        );
        $response = $this->controller->create($request);
        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('already exists', $data['error']);
    }

    public function testListBooks(): void
    {
        // Create test book entity
        $book = new Book();
        $book->setSerialNumber('222222');
        $book->setTitle('Listed Book');
        $book->setAuthor('Some Author');

        // Mock repo to return this book
        $this->repo->expects($this->once())
            ->method('findAll')
            ->willReturn([$book]);

        $response = $this->controller->list();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals('222222', $data[0]['serialNumber']);
    }

    public function testDeleteBook(): void
    {
        // Create test book entity
        $book = new Book();
        $book->setSerialNumber('333333');
        $book->setTitle('Book to Delete');
        $book->setAuthor('Author');

        // Mock repo to find and return the book
        $this->repo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($book);

        // Mock em to remove and flush
        $this->em->expects($this->once())->method('remove')->with($book);
        $this->em->expects($this->once())->method('flush');

        $response = $this->controller->delete(1);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testDeleteBookNotFound(): void
    {
        // Mock repo to return null (book not found)
        $this->repo->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $response = $this->controller->delete(999);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Not found', $data['error']);
    }

    public function testBorrowBook(): void
    {
        // Create test book entity (not borrowed)
        $book = new Book();
        $book->setSerialNumber('444444');
        $book->setTitle('Borrowable Book');
        $book->setAuthor('Some Author');

        // Mock repo to find and return the book
        $this->repo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($book);

        // Mock em to flush
        $this->em->expects($this->once())->method('flush');

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'action' => 'borrow',
                'cardNumber' => '333333'
            ])
        );
        $response = $this->controller->state(1, $request);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['isBorrowed']);
        $this->assertNotNull($data['borrowedAt']);
        $this->assertEquals('333333', $data['borrowedByCardNumber']);
    }

    public function testReturnBook(): void
    {
        // Create test book entity (borrowed)
        $book = new Book();
        $book->setSerialNumber('555555');
        $book->setTitle('Returned Book');
        $book->setAuthor('Author');
        $book->setIsBorrowed(true);
        $book->setBorrowedAt(new \DateTimeImmutable());
        $book->setBorrowedByCardNumber('654321');

        // Mock repo to find and return the book
        $this->repo->expects($this->once())
            ->method('find')
            ->with(2)
            ->willReturn($book);

        // Mock em to flush
        $this->em->expects($this->once())->method('flush');

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'action' => 'return'
            ])
        );
        $response = $this->controller->state(2, $request);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['isBorrowed']);
        $this->assertNull($data['borrowedAt']);
        $this->assertNull($data['borrowedByCardNumber']);
    }

    public function testBorrowWithInvalidCardNumber(): void
    {
        // Create test book entity
        $book = new Book();
        $book->setSerialNumber('666666');
        $book->setTitle('Book');
        $book->setAuthor('Author');

        // Mock repo to find the book
        $this->repo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($book);

        // No flush expected due to validation error
        $this->em->expects($this->never())->method('flush');

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'action' => 'borrow',
                'cardNumber' => '12345'  // Invalid: not 6 digits
            ])
        );
        $response = $this->controller->state(1, $request);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('6-digit', $data['error']);
    }
}

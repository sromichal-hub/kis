<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\BookRepository;

#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\Table(name: 'books')]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'serial_number', type: 'string', length: 6, unique: true)]
    private string $serialNumber;

    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(name: 'author', type: 'string', length: 255)]
    private string $author;

    #[ORM\Column(name: 'is_borrowed', type: 'boolean')]
    private bool $isBorrowed = false;

    #[ORM\Column(name: 'borrowed_at', type: 'datetimetz', nullable: true)]
    private ?\DateTimeInterface $borrowedAt = null;

    #[ORM\Column(name: 'borrowed_by_card_number', type: 'string', length: 6, nullable: true)]
    private ?string $borrowedByCardNumber = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSerialNumber(): string
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(string $serialNumber): self
    {
        $this->serialNumber = $serialNumber;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function isBorrowed(): bool
    {
        return $this->isBorrowed;
    }

    public function setIsBorrowed(bool $isBorrowed): self
    {
        $this->isBorrowed = $isBorrowed;

        return $this;
    }

    public function getBorrowedAt(): ?\DateTimeInterface
    {
        return $this->borrowedAt;
    }

    public function setBorrowedAt(?\DateTimeInterface $borrowedAt): self
    {
        $this->borrowedAt = $borrowedAt;

        return $this;
    }

    public function getBorrowedByCardNumber(): ?string
    {
        return $this->borrowedByCardNumber;
    }

    public function setBorrowedByCardNumber(?string $borrowedByCardNumber): self
    {
        $this->borrowedByCardNumber = $borrowedByCardNumber;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'serialNumber' => $this->serialNumber,
            'title' => $this->title,
            'author' => $this->author,
            'isBorrowed' => $this->isBorrowed,
            'borrowedAt' => $this->borrowedAt ? $this->borrowedAt->format(DATE_ATOM) : null,
            'borrowedByCardNumber' => $this->borrowedByCardNumber,
        ];
    }
}


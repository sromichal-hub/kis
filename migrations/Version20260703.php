<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260703 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create books table for library API';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE books (
    id SERIAL PRIMARY KEY,
    serial_number VARCHAR(6) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    is_borrowed BOOLEAN NOT NULL DEFAULT false,
    borrowed_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    borrowed_by_card_number VARCHAR(6) DEFAULT NULL
);
SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE books');
    }
}


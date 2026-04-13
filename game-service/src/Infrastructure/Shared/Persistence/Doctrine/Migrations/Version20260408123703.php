<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260408123703 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE answers (round_id VARCHAR(36) NOT NULL, guess VARCHAR(20) NOT NULL, is_correct TINYINT NOT NULL, response_time_ms INT NOT NULL, submitted_at DATETIME NOT NULL, id VARCHAR(36) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE rounds (session_id VARCHAR(36) NOT NULL, round_number INT NOT NULL, note_id VARCHAR(50) DEFAULT NULL, correct_note VARCHAR(10) DEFAULT NULL, started_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, id VARCHAR(36) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sessions (status VARCHAR(20) NOT NULL, difficulty INT NOT NULL, total_rounds INT NOT NULL, current_round INT NOT NULL, score INT NOT NULL, created_at DATETIME NOT NULL, id VARCHAR(36) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE answers');
        $this->addSql('DROP TABLE rounds');
        $this->addSql('DROP TABLE sessions');
    }
}

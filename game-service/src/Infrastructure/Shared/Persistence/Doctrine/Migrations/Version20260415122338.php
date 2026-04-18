<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415122338 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create rooms, room_players, room_rounds and room_round_answers tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE rooms (
            id VARCHAR(36) NOT NULL,
            code VARCHAR(6) NOT NULL,
            status VARCHAR(20) NOT NULL,
            difficulty INT NOT NULL,
            total_rounds INT NOT NULL,
            current_round_number INT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY UNIQ_room_code (code)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE room_players (
            id VARCHAR(36) NOT NULL,
            room_id VARCHAR(36) NOT NULL,
            name VARCHAR(100) NOT NULL,
            is_host TINYINT(1) NOT NULL,
            score INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY IDX_room_players_room_id (room_id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE room_rounds (
            id VARCHAR(36) NOT NULL,
            room_id VARCHAR(36) NOT NULL,
            round_number INT NOT NULL,
            note_id VARCHAR(50) DEFAULT NULL,
            correct_note VARCHAR(10) DEFAULT NULL,
            started_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY IDX_room_rounds_room_id (room_id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE room_round_answers (
            round_id VARCHAR(36) NOT NULL,
            player_id VARCHAR(36) NOT NULL,
            PRIMARY KEY (round_id, player_id)
        ) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE room_round_answers');
        $this->addSql('DROP TABLE room_rounds');
        $this->addSql('DROP TABLE room_players');
        $this->addSql('DROP TABLE rooms');
    }
}

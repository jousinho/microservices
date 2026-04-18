<?php

declare(strict_types=1);

namespace App\Infrastructure\Game\Persistence\Doctrine;

use App\Domain\Game\Entity\Player;
use App\Domain\Game\Entity\Room;
use App\Domain\Game\Entity\RoomRound;
use App\Domain\Game\Repository\RoomRepositoryInterface;
use App\Domain\Game\ValueObject\RoomStatus;
use Doctrine\DBAL\Connection;

final class DoctrineRoomRepository implements RoomRepositoryInterface
{
    public function __construct(private readonly Connection $connection) {}

    public function save(Room $room): void
    {
        $this->upsertRoom($room);
        $this->upsertPlayers($room);
        $this->upsertRounds($room);
        $this->saveAnsweredPlayers($room);
    }

    public function findById(string $id): ?Room
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM rooms WHERE id = ?',
            [$id],
        );

        if ($row === false) {
            return null;
        }

        return $this->reconstitute($row);
    }

    public function findByCode(string $code): ?Room
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM rooms WHERE code = ?',
            [$code],
        );

        if ($row === false) {
            return null;
        }

        return $this->reconstitute($row);
    }

    private function reconstitute(array $row): Room
    {
        $players = $this->fetchPlayers($row['id']);
        $rounds  = $this->fetchRounds($row['id']);

        $currentRound = null;
        foreach ($rounds as $round) {
            if ($round->roundNumber() === (int) $row['current_round_number']) {
                $currentRound = $round;
                break;
            }
        }

        $answeredPlayerIds = $currentRound !== null
            ? $this->fetchAnsweredPlayerIds($currentRound->id())
            : [];

        return Room::reconstitute(
            id:                               $row['id'],
            code:                             $row['code'],
            status:                           RoomStatus::from($row['status']),
            difficulty:                       (int) $row['difficulty'],
            totalRounds:                      (int) $row['total_rounds'],
            currentRoundNumber:               (int) $row['current_round_number'],
            createdAt:                        new \DateTimeImmutable($row['created_at']),
            players:                          $players,
            rounds:                           $rounds,
            playerIdsWhoAnsweredCurrentRound: $answeredPlayerIds,
        );
    }

    /** @return Player[] */
    private function fetchPlayers(string $roomId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM room_players WHERE room_id = ?',
            [$roomId],
        );

        return array_map(
            fn(array $row) => Player::reconstitute(
                id:     $row['id'],
                roomId: $row['room_id'],
                name:   $row['name'],
                isHost: (bool) $row['is_host'],
                score:  (int) $row['score'],
            ),
            $rows,
        );
    }

    /** @return RoomRound[] */
    private function fetchRounds(string $roomId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM room_rounds WHERE room_id = ? ORDER BY round_number',
            [$roomId],
        );

        return array_map(
            fn(array $row) => RoomRound::reconstitute(
                id:          $row['id'],
                roomId:      $row['room_id'],
                roundNumber: (int) $row['round_number'],
                startedAt:   new \DateTimeImmutable($row['started_at']),
                noteId:      $row['note_id'],
                correctNote: $row['correct_note'],
            ),
            $rows,
        );
    }

    /** @return string[] */
    private function fetchAnsweredPlayerIds(string $roundId): array
    {
        return $this->connection->fetchFirstColumn(
            'SELECT player_id FROM room_round_answers WHERE round_id = ?',
            [$roundId],
        );
    }

    private function upsertRoom(Room $room): void
    {
        $this->connection->executeStatement(
            'INSERT INTO rooms (id, code, status, difficulty, total_rounds, current_round_number, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                current_round_number = VALUES(current_round_number)',
            [
                $room->id(),
                $room->code()->value(),
                $room->status()->value,
                $room->difficulty()->value(),
                $room->totalRounds(),
                $room->currentRoundNumber(),
                $room->createdAt()->format('Y-m-d H:i:s'),
            ],
        );
    }

    private function upsertPlayers(Room $room): void
    {
        foreach ($room->players() as $player) {
            $this->connection->executeStatement(
                'INSERT INTO room_players (id, room_id, name, is_host, score)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE score = VALUES(score)',
                [
                    $player->id(),
                    $player->roomId(),
                    $player->name(),
                    $player->isHost() ? 1 : 0,
                    $player->score(),
                ],
            );
        }
    }

    private function upsertRounds(Room $room): void
    {
        foreach ($room->rounds() as $round) {
            $this->connection->executeStatement(
                'INSERT INTO room_rounds (id, room_id, round_number, note_id, correct_note, started_at)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    note_id = VALUES(note_id),
                    correct_note = VALUES(correct_note)',
                [
                    $round->id(),
                    $round->roomId(),
                    $round->roundNumber(),
                    $round->noteId(),
                    $round->correctNote(),
                    $round->startedAt()->format('Y-m-d H:i:s'),
                ],
            );
        }
    }

    private function saveAnsweredPlayers(Room $room): void
    {
        $currentRound = $room->currentRound();

        if ($currentRound === null) {
            return;
        }

        foreach ($room->answeredPlayerIdsCurrentRound() as $playerId) {
            $this->connection->executeStatement(
                'INSERT IGNORE INTO room_round_answers (round_id, player_id) VALUES (?, ?)',
                [$currentRound->id(), $playerId],
            );
        }
    }
}

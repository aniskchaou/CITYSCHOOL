<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class CourseController extends AbstractController
{
    #[Route('/course', name: 'course')]
    public function index(Connection $connection)
    {
        $courses = $this->fetchCoursesFromDatabase($connection);

        return $this->render('course/index.html.twig', [
            'courses' => $courses,
        ]);
    }

    private function fetchCoursesFromDatabase(Connection $connection): array
    {
        try {
            $schemaManager = $connection->createSchemaManager();
            $tableNames = $schemaManager->listTableNames();
        } catch (\Throwable $exception) {
            return [];
        }

        $tableName = null;
        foreach ($tableNames as $name) {
            $normalized = strtolower($name);
            if ($normalized === 'courses' || $normalized === 'course') {
                $tableName = $name;
                break;
            }
        }

        if ($tableName === null) {
            foreach ($tableNames as $name) {
                if (str_contains(strtolower($name), 'course')) {
                    $tableName = $name;
                    break;
                }
            }
        }

        if ($tableName === null) {
            return [];
        }

        try {
            $columns = $schemaManager->listTableColumns($tableName);
        } catch (\Throwable $exception) {
            return [];
        }

        $columnNames = [];
        foreach ($columns as $column) {
            $columnNames[strtolower($column->getName())] = $column->getName();
        }

        $resolve = static function (array $available, array $candidates): ?string {
            foreach ($candidates as $candidate) {
                if (isset($available[$candidate])) {
                    return $available[$candidate];
                }
            }

            return null;
        };

        $titleColumn = $resolve($columnNames, ['title', 'name', 'course_name']);
        if ($titleColumn === null) {
            return [];
        }

        $descriptionColumn = $resolve($columnNames, ['description', 'desc', 'summary', 'details']);
        $teacherColumn = $resolve($columnNames, ['teacher', 'teacher_name', 'instructor', 'instructor_name']);
        $scheduleColumn = $resolve($columnNames, ['schedule', 'time', 'timeslot', 'class_time']);
        $priceColumn = $resolve($columnNames, ['price', 'fee', 'cost']);
        $seatsColumn = $resolve($columnNames, ['seats', 'capacity', 'available_seats']);

        $platform = $connection->getDatabasePlatform();
        $quotedTable = $platform->quoteIdentifier($tableName);
        $quotedTitle = $platform->quoteIdentifier($titleColumn);

        $selectParts = [
            $quotedTitle . ' AS title',
        ];

        if ($descriptionColumn !== null) {
            $selectParts[] = $platform->quoteIdentifier($descriptionColumn) . ' AS description';
        }
        if ($teacherColumn !== null) {
            $selectParts[] = $platform->quoteIdentifier($teacherColumn) . ' AS teacher';
        }
        if ($scheduleColumn !== null) {
            $selectParts[] = $platform->quoteIdentifier($scheduleColumn) . ' AS schedule';
        }
        if ($priceColumn !== null) {
            $selectParts[] = $platform->quoteIdentifier($priceColumn) . ' AS price';
        }
        if ($seatsColumn !== null) {
            $selectParts[] = $platform->quoteIdentifier($seatsColumn) . ' AS seats';
        }

        $sql = 'SELECT ' . implode(', ', $selectParts) . ' FROM ' . $quotedTable . ' LIMIT 12';

        try {
            $rows = $connection->fetchAllAssociative($sql);
        } catch (\Throwable $exception) {
            return [];
        }

        $courses = [];
        foreach ($rows as $row) {
            $courses[] = [
                'title' => (string) ($row['title'] ?? 'Untitled course'),
                'description' => trim((string) ($row['description'] ?? 'Course details will be available soon.')),
                'teacher' => trim((string) ($row['teacher'] ?? 'TBA')),
                'schedule' => trim((string) ($row['schedule'] ?? 'TBA')),
                'price' => trim((string) ($row['price'] ?? 'TBA')),
                'seats' => trim((string) ($row['seats'] ?? 'N/A')),
            ];
        }

        return $courses;
    }
}

<?php

namespace App\Data;

class SampleData
{
    public static function getCourses(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Data Structures & Algorithms',
                'code' => 'CS201',
                'color' => 'primary',
                'progress' => 75,
                'instructor' => 'Dr. Sarah Chen',
                'schedule' => 'Mon, Wed 10:00 - 11:30',
                'room' => 'Tech Building 301',
                'credits' => 4,
                'notes_count' => 12,
                'assignments_count' => 5,
            ],
            [
                'id' => 2,
                'name' => 'Molecular Biology',
                'code' => 'BIO301',
                'color' => 'secondary',
                'progress' => 60,
                'instructor' => 'Prof. James Miller',
                'schedule' => 'Tue, Thu 14:00 - 15:30',
                'room' => 'Science Hall 205',
                'credits' => 3,
                'notes_count' => 8,
                'assignments_count' => 4,
            ],
            [
                'id' => 3,
                'name' => 'Calculus III',
                'code' => 'MATH301',
                'color' => 'accent',
                'progress' => 45,
                'instructor' => 'Dr. Emily Watson',
                'schedule' => 'Mon, Wed, Fri 09:00 - 10:00',
                'room' => 'Math Building 102',
                'credits' => 4,
                'notes_count' => 15,
                'assignments_count' => 8,
            ],
            [
                'id' => 4,
                'name' => 'Modern Philosophy',
                'code' => 'PHIL202',
                'color' => 'success',
                'progress' => 90,
                'instructor' => 'Dr. Michael Brown',
                'schedule' => 'Tue 16:00 - 18:00',
                'room' => 'Humanities 401',
                'credits' => 3,
                'notes_count' => 6,
                'assignments_count' => 2,
            ],
            [
                'id' => 5,
                'name' => 'Database Systems',
                'code' => 'CS305',
                'color' => 'warning',
                'progress' => 55,
                'instructor' => 'Prof. Lisa Park',
                'schedule' => 'Wed, Fri 13:00 - 14:30',
                'room' => 'Tech Building 205',
                'credits' => 3,
                'notes_count' => 10,
                'assignments_count' => 6,
            ],
            [
                'id' => 6,
                'name' => 'Technical Writing',
                'code' => 'ENG205',
                'color' => 'danger',
                'progress' => 80,
                'instructor' => 'Dr. Amanda Foster',
                'schedule' => 'Thu 10:00 - 12:00',
                'room' => 'Liberal Arts 110',
                'credits' => 2,
                'notes_count' => 4,
                'assignments_count' => 3,
            ],
        ];
    }

    public static function getAssignments(): array
    {
        $today = new \DateTime();
<<<<<<< HEAD
        
=======

>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
        return [
            [
                'id' => 1,
                'title' => 'Binary Tree Implementation',
                'course' => 'Data Structures & Algorithms',
                'course_code' => 'CS201',
                'due_date' => (clone $today)->modify('+1 day')->format('Y-m-d'),
                'priority' => 'high',
                'status' => 'in_progress',
                'progress' => 65,
                'description' => 'Implement a balanced binary search tree with insert, delete, and search operations.',
            ],
            [
                'id' => 2,
                'title' => 'Cell Division Lab Report',
                'course' => 'Molecular Biology',
                'course_code' => 'BIO301',
                'due_date' => (clone $today)->modify('+3 days')->format('Y-m-d'),
                'priority' => 'high',
                'status' => 'pending',
                'progress' => 20,
                'description' => 'Write a comprehensive report on the mitosis observation lab.',
            ],
            [
                'id' => 3,
                'title' => 'Triple Integrals Problem Set',
                'course' => 'Calculus III',
                'course_code' => 'MATH301',
                'due_date' => (clone $today)->modify('+2 days')->format('Y-m-d'),
                'priority' => 'medium',
                'status' => 'pending',
                'progress' => 0,
                'description' => 'Complete problems 1-20 from Chapter 15.',
            ],
            [
                'id' => 4,
                'title' => 'Descartes Essay',
                'course' => 'Modern Philosophy',
                'course_code' => 'PHIL202',
                'due_date' => (clone $today)->modify('+7 days')->format('Y-m-d'),
                'priority' => 'medium',
                'status' => 'pending',
                'progress' => 0,
                'description' => 'Write a 2000-word essay on Cartesian dualism.',
            ],
            [
                'id' => 5,
                'title' => 'SQL Query Optimization',
                'course' => 'Database Systems',
                'course_code' => 'CS305',
                'due_date' => (clone $today)->modify('+4 days')->format('Y-m-d'),
                'priority' => 'high',
                'status' => 'in_progress',
                'progress' => 40,
                'description' => 'Optimize the given queries and explain your approach.',
            ],
            [
                'id' => 6,
                'title' => 'Technical Manual Draft',
                'course' => 'Technical Writing',
                'course_code' => 'ENG205',
                'due_date' => (clone $today)->modify('+5 days')->format('Y-m-d'),
                'priority' => 'low',
                'status' => 'pending',
                'progress' => 0,
                'description' => 'First draft of the software documentation manual.',
            ],
            [
                'id' => 7,
                'title' => 'Graph Algorithms Quiz',
                'course' => 'Data Structures & Algorithms',
                'course_code' => 'CS201',
                'due_date' => (clone $today)->modify('-1 day')->format('Y-m-d'),
                'priority' => 'high',
                'status' => 'completed',
                'progress' => 100,
                'description' => 'Online quiz covering BFS, DFS, and shortest path algorithms.',
            ],
            [
                'id' => 8,
                'title' => 'ER Diagram Project',
                'course' => 'Database Systems',
                'course_code' => 'CS305',
                'due_date' => (clone $today)->modify('+10 days')->format('Y-m-d'),
                'priority' => 'medium',
                'status' => 'pending',
                'progress' => 10,
                'description' => 'Design an ER diagram for the university registration system.',
            ],
            [
                'id' => 9,
                'title' => 'Protein Synthesis Presentation',
                'course' => 'Molecular Biology',
                'course_code' => 'BIO301',
                'due_date' => (clone $today)->modify('+6 days')->format('Y-m-d'),
                'priority' => 'medium',
                'status' => 'pending',
                'progress' => 0,
                'description' => 'Prepare a 15-minute presentation on transcription and translation.',
            ],
            [
                'id' => 10,
                'title' => 'Vector Fields Homework',
                'course' => 'Calculus III',
                'course_code' => 'MATH301',
                'due_date' => (clone $today)->modify('-2 days')->format('Y-m-d'),
                'priority' => 'medium',
                'status' => 'completed',
                'progress' => 100,
                'description' => 'Problems on gradient, divergence, and curl.',
            ],
        ];
    }

    public static function getEvents(): array
    {
        $today = new \DateTime();
        $events = [];
<<<<<<< HEAD
        
        // Regular class schedule
=======

>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
        $classSchedule = [
            ['name' => 'Data Structures', 'color' => 'primary', 'time' => '10:00', 'duration' => 90, 'days' => [1, 3]],
            ['name' => 'Molecular Biology', 'color' => 'secondary', 'time' => '14:00', 'duration' => 90, 'days' => [2, 4]],
            ['name' => 'Calculus III', 'color' => 'accent', 'time' => '09:00', 'duration' => 60, 'days' => [1, 3, 5]],
            ['name' => 'Philosophy', 'color' => 'success', 'time' => '16:00', 'duration' => 120, 'days' => [2]],
            ['name' => 'Database Systems', 'color' => 'warning', 'time' => '13:00', 'duration' => 90, 'days' => [3, 5]],
            ['name' => 'Technical Writing', 'color' => 'danger', 'time' => '10:00', 'duration' => 120, 'days' => [4]],
        ];

        $id = 1;
<<<<<<< HEAD
        
        // Generate events for the next 14 days
        for ($i = 0; $i < 14; $i++) {
            $date = (clone $today)->modify("+{$i} days");
            $dayOfWeek = (int) $date->format('N');
            
=======

        for ($i = 0; $i < 14; $i++) {
            $date = (clone $today)->modify("+{$i} days");
            $dayOfWeek = (int) $date->format('N');

>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
            foreach ($classSchedule as $class) {
                if (in_array($dayOfWeek, $class['days'])) {
                    $events[] = [
                        'id' => $id++,
                        'title' => $class['name'],
                        'type' => 'class',
                        'color' => $class['color'],
                        'date' => $date->format('Y-m-d'),
                        'start_time' => $class['time'],
                        'duration' => $class['duration'],
                        'location' => 'Campus',
                    ];
                }
            }
        }

<<<<<<< HEAD
        // Add special events and conflicts
=======
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
        $specialEvents = [
            [
                'id' => $id++,
                'title' => 'Study Group - Algorithms',
                'type' => 'study',
                'color' => 'primary',
                'date' => (clone $today)->modify('+1 day')->format('Y-m-d'),
                'start_time' => '18:00',
                'duration' => 120,
                'location' => 'Library Room 3',
            ],
            [
                'id' => $id++,
                'title' => 'CS Department Career Fair',
                'type' => 'event',
                'color' => 'secondary',
                'date' => (clone $today)->modify('+3 days')->format('Y-m-d'),
<<<<<<< HEAD
                'start_time' => '14:00', // Conflicts with Molecular Biology
=======
                'start_time' => '14:00',
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
                'duration' => 180,
                'location' => 'Student Center',
                'conflict' => true,
            ],
            [
                'id' => $id++,
                'title' => 'Office Hours - Dr. Chen',
                'type' => 'office_hours',
                'color' => 'primary',
                'date' => (clone $today)->modify('+2 days')->format('Y-m-d'),
                'start_time' => '15:00',
                'duration' => 60,
                'location' => 'Tech Building 301',
            ],
            [
                'id' => $id++,
                'title' => 'Midterm Exam - Calculus',
                'type' => 'exam',
                'color' => 'danger',
                'date' => (clone $today)->modify('+5 days')->format('Y-m-d'),
                'start_time' => '09:00',
                'duration' => 120,
                'location' => 'Exam Hall A',
            ],
            [
                'id' => $id++,
                'title' => 'Project Meeting',
                'type' => 'meeting',
                'color' => 'warning',
                'date' => (clone $today)->modify('+1 day')->format('Y-m-d'),
<<<<<<< HEAD
                'start_time' => '14:00', // Conflicts with Biology on Tue/Thu
=======
                'start_time' => '14:00',
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
                'duration' => 60,
                'location' => 'Online - Zoom',
                'conflict' => true,
            ],
        ];

        return array_merge($events, $specialEvents);
    }

    public static function getFlashcardDecks(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Data Structures Fundamentals',
                'course' => 'Data Structures & Algorithms',
                'color' => 'primary',
                'card_count' => 25,
                'mastered_count' => 18,
                'last_studied' => '2 hours ago',
            ],
            [
                'id' => 2,
                'name' => 'Cell Biology Terms',
                'course' => 'Molecular Biology',
                'color' => 'secondary',
                'card_count' => 40,
                'mastered_count' => 22,
                'last_studied' => 'Yesterday',
            ],
            [
                'id' => 3,
                'name' => 'Calculus Formulas',
                'course' => 'Calculus III',
                'color' => 'accent',
                'card_count' => 30,
                'mastered_count' => 12,
                'last_studied' => '3 days ago',
            ],
        ];
    }

    public static function getFlashcards(): array
    {
        return [
<<<<<<< HEAD
            // Data Structures deck
=======
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
            ['id' => 1, 'deck_id' => 1, 'front' => 'What is the time complexity of binary search?', 'back' => 'O(log n)', 'mastered' => true],
            ['id' => 2, 'deck_id' => 1, 'front' => 'What is a stack?', 'back' => 'A LIFO (Last In, First Out) data structure', 'mastered' => true],
            ['id' => 3, 'deck_id' => 1, 'front' => 'What is the difference between BFS and DFS?', 'back' => 'BFS explores level by level (uses queue), DFS explores depth first (uses stack)', 'mastered' => false],
            ['id' => 4, 'deck_id' => 1, 'front' => 'What is a hash collision?', 'back' => 'When two different keys hash to the same index', 'mastered' => true],
            ['id' => 5, 'deck_id' => 1, 'front' => 'Time complexity of inserting into a balanced BST?', 'back' => 'O(log n)', 'mastered' => false],
<<<<<<< HEAD
            
            // Biology deck
=======

>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
            ['id' => 6, 'deck_id' => 2, 'front' => 'What is mitosis?', 'back' => 'Cell division resulting in two identical daughter cells', 'mastered' => true],
            ['id' => 7, 'deck_id' => 2, 'front' => 'What are the four phases of mitosis?', 'back' => 'Prophase, Metaphase, Anaphase, Telophase', 'mastered' => true],
            ['id' => 8, 'deck_id' => 2, 'front' => 'What is mRNA?', 'back' => 'Messenger RNA - carries genetic information from DNA to ribosomes', 'mastered' => false],
            ['id' => 9, 'deck_id' => 2, 'front' => 'What is the function of ribosomes?', 'back' => 'Protein synthesis - translating mRNA into proteins', 'mastered' => true],
            ['id' => 10, 'deck_id' => 2, 'front' => 'What is ATP?', 'back' => 'Adenosine triphosphate - the primary energy carrier in cells', 'mastered' => false],
<<<<<<< HEAD
            
            // Calculus deck
=======

>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
            ['id' => 11, 'deck_id' => 3, 'front' => 'What is the gradient of a function?', 'back' => 'A vector of partial derivatives pointing in the direction of steepest ascent', 'mastered' => true],
            ['id' => 12, 'deck_id' => 3, 'front' => 'What is divergence?', 'back' => 'The scalar measure of a vector field\'s source or sink at a point', 'mastered' => false],
            ['id' => 13, 'deck_id' => 3, 'front' => 'What is curl?', 'back' => 'A vector measure of rotation or circulation at a point in a vector field', 'mastered' => false],
            ['id' => 14, 'deck_id' => 3, 'front' => 'State Green\'s Theorem', 'back' => 'Relates a line integral around a curve to a double integral over the region it encloses', 'mastered' => true],
            ['id' => 15, 'deck_id' => 3, 'front' => 'What is a line integral?', 'back' => 'An integral where the function is evaluated along a curve', 'mastered' => false],
        ];
    }

    public static function getProjects(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Database Design Project',
                'course' => 'Database Systems',
                'color' => 'warning',
                'members' => ['Amine B.', 'Sarah K.', 'Mike L.'],
                'due_date' => (new \DateTime())->modify('+14 days')->format('Y-m-d'),
                'progress' => 35,
            ],
            [
                'id' => 2,
                'name' => 'Algorithm Visualization Tool',
                'course' => 'Data Structures & Algorithms',
                'color' => 'primary',
                'members' => ['Amine B.', 'John D.'],
                'due_date' => (new \DateTime())->modify('+21 days')->format('Y-m-d'),
                'progress' => 20,
            ],
        ];
    }

    public static function getKanbanTasks(): array
    {
        return [
<<<<<<< HEAD
            // Project 1 tasks
=======
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
            ['id' => 1, 'project_id' => 1, 'title' => 'Requirements gathering', 'status' => 'done', 'assignee' => 'Amine B.'],
            ['id' => 2, 'project_id' => 1, 'title' => 'Create ER diagram', 'status' => 'done', 'assignee' => 'Sarah K.'],
            ['id' => 3, 'project_id' => 1, 'title' => 'Design database schema', 'status' => 'in_progress', 'assignee' => 'Amine B.'],
            ['id' => 4, 'project_id' => 1, 'title' => 'Implement tables', 'status' => 'in_progress', 'assignee' => 'Mike L.'],
            ['id' => 5, 'project_id' => 1, 'title' => 'Write stored procedures', 'status' => 'todo', 'assignee' => 'Sarah K.'],
            ['id' => 6, 'project_id' => 1, 'title' => 'Create test data', 'status' => 'todo', 'assignee' => 'Mike L.'],
            ['id' => 7, 'project_id' => 1, 'title' => 'Performance testing', 'status' => 'todo', 'assignee' => 'Amine B.'],
<<<<<<< HEAD
            ['id' => 8, 'project_id' => 1, 'title' => 'Documentation', 'status' => 'todo', 'assignee' => 'Sarah K.'],
            
            // Project 2 tasks
=======
            ['id' => 8, 'project_id' => 1, 'title' => 'Documentation', 'status' => 'todo', 'assignee' => 'Amine B.'],

>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
            ['id' => 9, 'project_id' => 2, 'title' => 'Setup React project', 'status' => 'done', 'assignee' => 'Amine B.'],
            ['id' => 10, 'project_id' => 2, 'title' => 'Design UI mockups', 'status' => 'in_progress', 'assignee' => 'John D.'],
            ['id' => 11, 'project_id' => 2, 'title' => 'Implement sorting visualizer', 'status' => 'todo', 'assignee' => 'Amine B.'],
            ['id' => 12, 'project_id' => 2, 'title' => 'Implement graph algorithms', 'status' => 'todo', 'assignee' => 'John D.'],
            ['id' => 13, 'project_id' => 2, 'title' => 'Add step-by-step mode', 'status' => 'todo', 'assignee' => 'Amine B.'],
            ['id' => 14, 'project_id' => 2, 'title' => 'Write user guide', 'status' => 'todo', 'assignee' => 'John D.'],
        ];
    }

    public static function getStressCheckins(): array
    {
        $checkins = [];
        $today = new \DateTime();
<<<<<<< HEAD
        
=======

>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
        for ($i = 9; $i >= 0; $i--) {
            $date = (clone $today)->modify("-{$i} days");
            $checkins[] = [
                'id' => 10 - $i,
                'date' => $date->format('Y-m-d'),
                'stress_level' => rand(2, 8),
                'energy_level' => rand(3, 9),
                'sleep_hours' => rand(5, 9),
                'mood' => ['great', 'good', 'okay', 'stressed', 'tired'][rand(0, 4)],
                'notes' => '',
            ];
        }
<<<<<<< HEAD
        
        return $checkins;
    }

=======

        return $checkins;
    }

    // ✅ UNIQUE FUNCTION (pas de doublon)
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
    public static function getCopingTools(): array
    {
        return [
            [
                'id' => 1,
<<<<<<< HEAD
                'name' => 'Breathing Exercise',
                'description' => '4-7-8 breathing technique for quick relaxation',
                'duration' => '3 min',
=======
                'key' => 'breathing',
                'name' => 'Breathing Exercise',
                'description' => '4-7-8 breathing technique for quick relaxation',
                'duration' => '3 min',
                'durationSeconds' => 180,
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
                'icon' => 'wind',
                'color' => 'primary',
            ],
            [
                'id' => 2,
<<<<<<< HEAD
                'name' => 'Quick Meditation',
                'description' => 'Guided mindfulness session',
                'duration' => '5 min',
=======
                'key' => 'meditation',
                'name' => 'Quick Meditation',
                'description' => 'Guided mindfulness session',
                'duration' => '5 min',
                'durationSeconds' => 300,
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
                'icon' => 'sparkles',
                'color' => 'secondary',
            ],
            [
                'id' => 3,
<<<<<<< HEAD
                'name' => 'Stretching Break',
                'description' => 'Desk-friendly stretches for tension relief',
                'duration' => '4 min',
=======
                'key' => 'stretching',
                'name' => 'Stretching Break',
                'description' => 'Desk-friendly stretches for tension relief',
                'duration' => '4 min',
                'durationSeconds' => 240,
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
                'icon' => 'heart',
                'color' => 'success',
            ],
            [
                'id' => 4,
<<<<<<< HEAD
                'name' => 'Pomodoro Timer',
                'description' => 'Focused work sessions with breaks',
                'duration' => '25 min',
=======
                'key' => 'pomodoro',
                'name' => 'Pomodoro Timer',
                'description' => 'Focused work sessions with breaks',
                'duration' => '25 min',
                'durationSeconds' => 1500,
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
                'icon' => 'clock',
                'color' => 'accent',
            ],
            [
                'id' => 5,
<<<<<<< HEAD
                'name' => 'Gratitude Journal',
                'description' => 'Write three things you\'re grateful for',
                'duration' => '2 min',
=======
                'key' => 'gratitude',
                'name' => 'Gratitude Journal',
                'description' => 'Write three things you\'re grateful for',
                'duration' => '2 min',
                'durationSeconds' => 120,
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
                'icon' => 'book',
                'color' => 'warning',
            ],
            [
                'id' => 6,
<<<<<<< HEAD
                'name' => 'Nature Sounds',
                'description' => 'Relaxing ambient sounds for focus',
                'duration' => 'Ongoing',
=======
                'key' => 'nature',
                'name' => 'Nature Sounds',
                'description' => 'Relaxing ambient sounds for focus',
                'duration' => 'Ongoing',
                'durationSeconds' => 0,
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
                'icon' => 'music',
                'color' => 'success',
            ],
        ];
    }

    public static function getActivityFeed(): array
    {
        return [
            ['type' => 'assignment', 'message' => 'Completed Graph Algorithms Quiz', 'time' => '2 hours ago', 'icon' => 'check'],
            ['type' => 'flashcard', 'message' => 'Studied 15 cards from Data Structures deck', 'time' => '3 hours ago', 'icon' => 'cards'],
            ['type' => 'note', 'message' => 'Added notes for Calculus III lecture', 'time' => '5 hours ago', 'icon' => 'document'],
            ['type' => 'streak', 'message' => '7-day study streak achieved!', 'time' => 'Yesterday', 'icon' => 'fire'],
            ['type' => 'assignment', 'message' => 'Started Binary Tree Implementation', 'time' => 'Yesterday', 'icon' => 'play'],
        ];
    }

    public static function getStats(): array
    {
        return [
            'assignments_due_soon' => 3,
            'assignments_completed_this_week' => 5,
            'study_hours_this_week' => 18,
            'flashcards_reviewed_today' => 45,
            'current_streak' => 7,
            'courses_on_track' => 4,
            'courses_behind' => 2,
            'upcoming_exams' => 1,
        ];
    }
}

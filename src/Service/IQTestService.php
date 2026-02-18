<?php

namespace App\Service;

class IQTestService
{
    // Tunisian Baccalaureate subjects and their topics
    private const SUBJECTS = [
        'Mathematics' => [
            'Algebra', 'Geometry', 'Trigonometry', 'Calculus', 
            'Statistics', 'Probability', 'Functions', 'Sequences'
        ],
        'Physics' => [
            'Mechanics', 'Electricity', 'Optics', 'Thermodynamics',
            'Waves', 'Nuclear Physics', 'Magnetism', 'Energy'
        ],
        'Chemistry' => [
            'Organic Chemistry', 'Inorganic Chemistry', 'Acids and Bases',
            'Redox Reactions', 'Chemical Kinetics', 'Thermochemistry'
        ],
        'Biology' => [
            'Cell Biology', 'Genetics', 'Evolution', 'Ecology',
            'Human Anatomy', 'Physiology', 'Microbiology'
        ],
        'Computer Science' => [
            'Algorithms', 'Data Structures', 'Programming', 'Networks',
            'Databases', 'Operating Systems', 'Boolean Logic'
        ],
        'Philosophy' => [
            'Ethics', 'Logic', 'Metaphysics', 'Epistemology',
            'Political Philosophy', 'Aesthetics'
        ],
        'History' => [
            'Ancient History', 'Medieval History', 'Modern History',
            'Tunisian History', 'World Wars', 'Civilizations'
        ],
        'Geography' => [
            'Physical Geography', 'Human Geography', 'Climate',
            'Natural Resources', 'Population', 'Urbanization'
        ],
        'Arabic' => [
            'Grammar', 'Literature', 'Poetry', 'Rhetoric',
            'Classical Texts', 'Modern Literature'
        ],
        'French' => [
            'Grammar', 'Literature', 'Comprehension', 'Expression',
            'French Culture', 'Francophone Literature'
        ],
        'English' => [
            'Grammar', 'Vocabulary', 'Comprehension', 'Writing',
            'Literature', 'Communication'
        ]
    ];

    private const DIFFICULTY_LEVELS = ['easy', 'medium', 'hard'];

    /**
     * Generate a complete IQ test with random questions from different subjects
     */
    public function generateIQTest(int $numberOfQuestions = 15): array
    {
        $questions = [];
        $usedQuestions = [];
        
        // Distribute questions across subjects
        $subjects = array_keys(self::SUBJECTS);
        $questionsPerSubject = ceil($numberOfQuestions / count($subjects));
        
        $questionId = 1;
        
        foreach ($subjects as $subject) {
            $topicsForSubject = self::SUBJECTS[$subject];
            $subjectQuestionCount = 0;
            
            while ($subjectQuestionCount < $questionsPerSubject && $questionId <= $numberOfQuestions) {
                // Random topic from this subject
                $topic = $topicsForSubject[array_rand($topicsForSubject)];
                
                // Random difficulty
                $difficulty = self::DIFFICULTY_LEVELS[array_rand(self::DIFFICULTY_LEVELS)];
                
                // Generate question
                $question = $this->generateQuestion($subject, $topic, $difficulty);
                
                // Avoid duplicates
                $questionHash = md5($question['question']);
                if (!in_array($questionHash, $usedQuestions)) {
                    $question['id'] = $questionId;
                    $questions[] = $question;
                    $usedQuestions[] = $questionHash;
                    $questionId++;
                    $subjectQuestionCount++;
                }
            }
            
            if ($questionId > $numberOfQuestions) {
                break;
            }
        }
        
        // Shuffle questions
        shuffle($questions);
        
        // Re-index questions
        foreach ($questions as $index => &$question) {
            $question['id'] = $index + 1;
        }
        
        return array_slice($questions, 0, $numberOfQuestions);
    }

    /**
     * Generate a single question based on subject, topic, and difficulty
     */
    private function generateQuestion(string $subject, string $topic, string $difficulty): array
    {
        $questionTemplates = $this->getQuestionTemplates($subject, $topic, $difficulty);
        $template = $questionTemplates[array_rand($questionTemplates)];
        
        return [
            'question' => $template['question'],
            'options' => $template['options'],
            'correct_answer' => $template['correct_answer'],
            'category' => "$subject - $topic",
            'difficulty' => $difficulty,
            'subject' => $subject,
            'topic' => $topic
        ];
    }

    /**
     * Get question templates based on subject, topic, and difficulty
     */
    private function getQuestionTemplates(string $subject, string $topic, string $difficulty): array
    {
        $templates = [];
        
        // Mathematics questions
        if ($subject === 'Mathematics') {
            $templates = match($topic) {
                'Algebra' => [
                    [
                        'question' => 'If 2x + 5 = 13, what is the value of x?',
                        'options' => ['4', '3', '5', '6'],
                        'correct_answer' => '4'
                    ],
                    [
                        'question' => 'Solve: 3(x - 2) = 15',
                        'options' => ['7', '5', '9', '6'],
                        'correct_answer' => '7'
                    ],
                    [
                        'question' => 'What is the value of x² - 4 when x = 3?',
                        'options' => ['5', '7', '9', '13'],
                        'correct_answer' => '5'
                    ]
                ],
                'Geometry' => [
                    [
                        'question' => 'What is the area of a triangle with base 10 and height 6?',
                        'options' => ['30', '60', '20', '40'],
                        'correct_answer' => '30'
                    ],
                    [
                        'question' => 'The sum of angles in a triangle equals:',
                        'options' => ['180°', '360°', '90°', '270°'],
                        'correct_answer' => '180°'
                    ]
                ],
                'Probability' => [
                    [
                        'question' => 'What is the probability of getting heads when flipping a fair coin?',
                        'options' => ['1/2', '1/4', '1/3', '2/3'],
                        'correct_answer' => '1/2'
                    ]
                ],
                default => [
                    [
                        'question' => 'What is 15% of 200?',
                        'options' => ['30', '25', '35', '40'],
                        'correct_answer' => '30'
                    ]
                ]
            };
        }
        
        // Physics questions
        elseif ($subject === 'Physics') {
            $templates = match($topic) {
                'Mechanics' => [
                    [
                        'question' => 'What is the SI unit of force?',
                        'options' => ['Newton', 'Joule', 'Watt', 'Pascal'],
                        'correct_answer' => 'Newton'
                    ],
                    [
                        'question' => 'According to Newton\'s second law, F = ?',
                        'options' => ['ma', 'mv', 'mgh', 'mv²'],
                        'correct_answer' => 'ma'
                    ]
                ],
                'Electricity' => [
                    [
                        'question' => 'What is Ohm\'s law?',
                        'options' => ['V = IR', 'P = IV', 'E = mc²', 'F = ma'],
                        'correct_answer' => 'V = IR'
                    ],
                    [
                        'question' => 'The unit of electrical resistance is:',
                        'options' => ['Ohm', 'Volt', 'Ampere', 'Watt'],
                        'correct_answer' => 'Ohm'
                    ]
                ],
                'Energy' => [
                    [
                        'question' => 'What is the formula for kinetic energy?',
                        'options' => ['½mv²', 'mgh', 'mc²', 'Fd'],
                        'correct_answer' => '½mv²'
                    ]
                ],
                default => [
                    [
                        'question' => 'What is the speed of light in vacuum?',
                        'options' => ['3×10⁸ m/s', '3×10⁶ m/s', '3×10⁷ m/s', '3×10⁹ m/s'],
                        'correct_answer' => '3×10⁸ m/s'
                    ]
                ]
            };
        }
        
        // Chemistry questions
        elseif ($subject === 'Chemistry') {
            $templates = match($topic) {
                'Acids and Bases' => [
                    [
                        'question' => 'What is the pH of a neutral solution?',
                        'options' => ['7', '0', '14', '1'],
                        'correct_answer' => '7'
                    ],
                    [
                        'question' => 'Which substance turns litmus paper red?',
                        'options' => ['Acid', 'Base', 'Salt', 'Water'],
                        'correct_answer' => 'Acid'
                    ]
                ],
                'Organic Chemistry' => [
                    [
                        'question' => 'What is the general formula for alkanes?',
                        'options' => ['CnH2n+2', 'CnH2n', 'CnH2n-2', 'CnHn'],
                        'correct_answer' => 'CnH2n+2'
                    ]
                ],
                default => [
                    [
                        'question' => 'What is the atomic number of Carbon?',
                        'options' => ['6', '12', '8', '14'],
                        'correct_answer' => '6'
                    ]
                ]
            };
        }
        
        // Biology questions
        elseif ($subject === 'Biology') {
            $templates = match($topic) {
                'Cell Biology' => [
                    [
                        'question' => 'What is the powerhouse of the cell?',
                        'options' => ['Mitochondria', 'Nucleus', 'Ribosome', 'Golgi apparatus'],
                        'correct_answer' => 'Mitochondria'
                    ],
                    [
                        'question' => 'Which organelle contains DNA?',
                        'options' => ['Nucleus', 'Ribosome', 'Lysosome', 'Vacuole'],
                        'correct_answer' => 'Nucleus'
                    ]
                ],
                'Genetics' => [
                    [
                        'question' => 'DNA stands for:',
                        'options' => ['Deoxyribonucleic Acid', 'Diribonucleic Acid', 'Deoxynucleic Acid', 'Dinucleic Acid'],
                        'correct_answer' => 'Deoxyribonucleic Acid'
                    ]
                ],
                default => [
                    [
                        'question' => 'What process do plants use to make food?',
                        'options' => ['Photosynthesis', 'Respiration', 'Fermentation', 'Digestion'],
                        'correct_answer' => 'Photosynthesis'
                    ]
                ]
            };
        }
        
        // Computer Science questions
        elseif ($subject === 'Computer Science') {
            $templates = match($topic) {
                'Algorithms' => [
                    [
                        'question' => 'What is the time complexity of binary search?',
                        'options' => ['O(log n)', 'O(n)', 'O(n²)', 'O(1)'],
                        'correct_answer' => 'O(log n)'
                    ]
                ],
                'Boolean Logic' => [
                    [
                        'question' => 'What is the result of: True AND False?',
                        'options' => ['False', 'True', 'Undefined', 'Error'],
                        'correct_answer' => 'False'
                    ]
                ],
                default => [
                    [
                        'question' => 'Which language is primarily used for web styling?',
                        'options' => ['CSS', 'HTML', 'JavaScript', 'Python'],
                        'correct_answer' => 'CSS'
                    ]
                ]
            };
        }
        
        // Philosophy questions
        elseif ($subject === 'Philosophy') {
            $templates = [
                [
                    'question' => 'Who said "I think, therefore I am"?',
                    'options' => ['Descartes', 'Plato', 'Aristotle', 'Kant'],
                    'correct_answer' => 'Descartes'
                ],
                [
                    'question' => 'What does epistemology study?',
                    'options' => ['Knowledge', 'Ethics', 'Beauty', 'Being'],
                    'correct_answer' => 'Knowledge'
                ]
            ];
        }
        
        // History questions
        elseif ($subject === 'History') {
            $templates = [
                [
                    'question' => 'In which year did Tunisia gain independence?',
                    'options' => ['1956', '1960', '1952', '1958'],
                    'correct_answer' => '1956'
                ],
                [
                    'question' => 'Who was the first president of Tunisia?',
                    'options' => ['Habib Bourguiba', 'Zine El Abidine Ben Ali', 'Carthage', 'Hannibal'],
                    'correct_answer' => 'Habib Bourguiba'
                ]
            ];
        }
        
        // Geography questions
        elseif ($subject === 'Geography') {
            $templates = [
                [
                    'question' => 'What is the capital of Tunisia?',
                    'options' => ['Tunis', 'Sfax', 'Sousse', 'Bizerte'],
                    'correct_answer' => 'Tunis'
                ],
                [
                    'question' => 'Which sea borders Tunisia to the north?',
                    'options' => ['Mediterranean Sea', 'Red Sea', 'Black Sea', 'Aegean Sea'],
                    'correct_answer' => 'Mediterranean Sea'
                ]
            ];
        }
        
        // Language questions (Arabic, French, English)
        else {
            $templates = [
                [
                    'question' => 'What is a synonym for "happy"?',
                    'options' => ['Joyful', 'Sad', 'Angry', 'Tired'],
                    'correct_answer' => 'Joyful'
                ],
                [
                    'question' => 'Which word is a noun?',
                    'options' => ['Table', 'Run', 'Beautiful', 'Quickly'],
                    'correct_answer' => 'Table'
                ]
            ];
        }
        
        return $templates;
    }

    /**
     * Calculate IQ score based on performance
     */
    public function calculateIQScore(int $correctAnswers, int $totalQuestions, int $timeSpent): array
    {
        // Base score calculation
        $percentage = ($correctAnswers / $totalQuestions) * 100;
        
        // Base IQ score (70-145 range)
        $baseIQ = 70 + ($percentage * 0.75);
        
        // Time bonus/penalty (optimal time: 30 seconds per question)
        $optimalTime = $totalQuestions * 30;
        $timeRatio = $timeSpent / $optimalTime;
        
        $timeFactor = 1.0;
        if ($timeRatio < 0.5) {
            // Too fast - might be guessing
            $timeFactor = 0.9;
        } elseif ($timeRatio > 2.0) {
            // Too slow
            $timeFactor = 0.95;
        } else {
            // Good timing
            $timeFactor = 1.05;
        }
        
        $finalIQ = round($baseIQ * $timeFactor);
        
        // Ensure IQ is within realistic bounds
        $finalIQ = max(70, min(145, $finalIQ));
        
        // Determine level
        $level = match(true) {
            $finalIQ >= 130 => 'Exceptionally Gifted',
            $finalIQ >= 120 => 'Very Superior Intelligence',
            $finalIQ >= 110 => 'Superior Intelligence',
            $finalIQ >= 90 => 'Average Intelligence',
            $finalIQ >= 80 => 'Below Average',
            default => 'Low Average'
        };
        
        return [
            'iq_score' => $finalIQ,
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'percentage' => round($percentage, 1),
            'time_spent' => $timeSpent,
            'average_time_per_question' => round($timeSpent / $totalQuestions, 1),
            'level' => $level
        ];
    }

    /**
     * Get semantic relations (for API testing)
     */
    public function getSemanticRelations(): array
    {
        return [
            ['word1' => 'Hot', 'word2' => 'Cold', 'relation' => 'Antonym'],
            ['word1' => 'Doctor', 'word2' => 'Hospital', 'relation' => 'Location'],
            ['word1' => 'Book', 'word2' => 'Read', 'relation' => 'Action'],
        ];
    }
}

<?php

class SubtitleProcessor {
    private $db;
    private $upload_dir;
    private $subtitle_dir;
    private $merged_dir;

    public function __construct($database) {
        $this->db = $database;
        $this->upload_dir = __DIR__ . '/../uploads/';
        $this->subtitle_dir = $this->upload_dir . 'subtitles/';
        $this->merged_dir = $this->upload_dir . 'merged_videos/';

        // Create directories if they don't exist
        $this->createDirectories();
    }

    private function createDirectories() {
        $directories = [$this->subtitle_dir, $this->merged_dir];
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * Upload and process subtitle file
     */
    public function uploadSubtitle($video_id, $subtitle_file) {
        try {
            // Validate subtitle file
            $allowed_extensions = ['srt', 'vtt', 'ass', 'ssa'];
            $file_extension = strtolower(pathinfo($subtitle_file['name'], PATHINFO_EXTENSION));

            if (!in_array($file_extension, $allowed_extensions)) {
                throw new Exception('Invalid subtitle file format. Supported: SRT, VTT, ASS, SSA');
            }

            // Generate unique filename
            $filename = 'subtitle_' . $video_id . '_' . time() . '.' . $file_extension;
            $file_path = $this->subtitle_dir . $filename;

            // Move uploaded file
            if (!move_uploaded_file($subtitle_file['tmp_name'], $file_path)) {
                throw new Exception('Failed to upload subtitle file');
            }

            // Insert subtitle record
            $conn = $this->db->getConnection();
            if ($conn === $this->db) {
                // File-based database
                $subtitle_id = $this->db->insert('subtitles', [
                    'video_id' => $video_id,
                    'original_file_path' => $file_path,
                    'language_from' => 'en',
                    'language_to' => 'ig',
                    'translation_status' => 'pending',
                    'merge_status' => 'pending'
                ]);
            } else {
                // MySQL database
                $stmt = $conn->prepare("INSERT INTO subtitles (video_id, original_file_path) VALUES (?, ?)");
                $stmt->execute([$video_id, $file_path]);
                $subtitle_id = $conn->lastInsertId();
            }

            return $subtitle_id;

        } catch (Exception $e) {
            throw new Exception('Subtitle upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Parse SRT subtitle file
     */
    public function parseSrtFile($file_path) {
        if (!file_exists($file_path)) {
            throw new Exception('Subtitle file not found');
        }

        $content = file_get_contents($file_path);
        $subtitles = [];

        // Split by double newlines to get subtitle blocks
        $blocks = preg_split('/\n\s*\n/', trim($content));

        foreach ($blocks as $block) {
            $lines = explode("\n", trim($block));
            if (count($lines) >= 3) {
                $sequence = trim($lines[0]);
                $timing = trim($lines[1]);
                $text = implode("\n", array_slice($lines, 2));

                // Parse timing
                if (preg_match('/(\d{2}:\d{2}:\d{2},\d{3})\s*-->\s*(\d{2}:\d{2}:\d{2},\d{3})/', $timing, $matches)) {
                    $subtitles[] = [
                        'sequence' => $sequence,
                        'start_time' => $matches[1],
                        'end_time' => $matches[2],
                        'text' => $text
                    ];
                }
            }
        }

        return $subtitles;
    }

    /**
     * Translate text using Google Translate API (mock implementation)
     * In production, you would integrate with Google Translate API or similar service
     */
    public function translateText($text, $from_lang = 'en', $to_lang = 'ig') {
        // Mock translation - in production, use actual translation service
        $translations = [
            'Hello' => 'Ndewo',
            'Welcome' => 'Nnọọ',
            'Thank you' => 'Daalu',
            'Good morning' => 'Ụtụtụ ọma',
            'Good evening' => 'Mgbede ọma',
            'How are you?' => 'Kedu ka ị mere?',
            'What is your name?' => 'Kedụ aha gị?',
            'I am fine' => 'Adị m mma',
            'Please' => 'Biko',
            'Sorry' => 'Ndo',
            'Yes' => 'Ee',
            'No' => 'Mba',
            'Today' => 'Taa',
            'Tomorrow' => 'Echi',
            'Yesterday' => 'Ụnyaahụ',
            'Water' => 'Mmiri',
            'Food' => 'Nri',
            'House' => 'Ụlọ',
            'School' => 'Ụlọ akwụkwọ',
            'Work' => 'Ọrụ'
        ];

        // Simple word-by-word translation for demo
        $words = explode(' ', $text);
        $translated_words = [];

        foreach ($words as $word) {
            $clean_word = preg_replace('/[^\w\s]/', '', $word);
            $translated_word = $translations[$clean_word] ?? $word;
            $translated_words[] = $translated_word;
        }

        return implode(' ', $translated_words);
    }

    /**
     * Translate entire subtitle file
     */
    public function translateSubtitleFile($subtitle_id) {
        try {
            $conn = $this->db->getConnection();

            // Get subtitle record
            if ($conn === $this->db) {
                $subtitle = $this->db->selectOne('subtitles', ['id' => $subtitle_id]);
            } else {
                $stmt = $conn->prepare("SELECT * FROM subtitles WHERE id = ?");
                $stmt->execute([$subtitle_id]);
                $subtitle = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if (!$subtitle) {
                throw new Exception('Subtitle not found');
            }

            // Update status to translating
            $this->updateSubtitleStatus($subtitle_id, 'translation_status', 'translating');

            // Parse original subtitle
            $subtitles = $this->parseSrtFile($subtitle['original_file_path']);

            // Translate each subtitle
            $translated_subtitles = [];
            foreach ($subtitles as $sub) {
                $translated_text = $this->translateText($sub['text']);
                $translated_subtitles[] = [
                    'sequence' => $sub['sequence'],
                    'start_time' => $sub['start_time'],
                    'end_time' => $sub['end_time'],
                    'text' => $translated_text
                ];
            }

            // Save translated subtitle file
            $translated_filename = 'subtitle_translated_' . $subtitle_id . '_' . time() . '.srt';
            $translated_path = $this->subtitle_dir . $translated_filename;

            $this->saveSrtFile($translated_path, $translated_subtitles);

            // Update database with translated file path
            if ($conn === $this->db) {
                $this->db->update('subtitles',
                    ['translated_file_path' => $translated_path, 'translation_status' => 'completed'],
                    ['id' => $subtitle_id]
                );
            } else {
                $stmt = $conn->prepare("UPDATE subtitles SET translated_file_path = ?, translation_status = 'completed' WHERE id = ?");
                $stmt->execute([$translated_path, $subtitle_id]);
            }

            return $translated_path;

        } catch (Exception $e) {
            $this->updateSubtitleStatus($subtitle_id, 'translation_status', 'failed');
            throw new Exception('Translation failed: ' . $e->getMessage());
        }
    }

    /**
     * Save translated subtitles to SRT file
     */
    private function saveSrtFile($file_path, $subtitles) {
        $content = '';
        foreach ($subtitles as $sub) {
            $content .= $sub['sequence'] . "\n";
            $content .= $sub['start_time'] . ' --> ' . $sub['end_time'] . "\n";
            $content .= $sub['text'] . "\n\n";
        }

        file_put_contents($file_path, $content);
    }

    /**
     * Merge subtitle with video using FFmpeg (requires FFmpeg installation)
     */
    public function mergeSubtitleWithVideo($subtitle_id) {
        try {
            $conn = $this->db->getConnection();

            // Get subtitle record
            if ($conn === $this->db) {
                $subtitle = $this->db->selectOne('subtitles', ['id' => $subtitle_id]);
            } else {
                $stmt = $conn->prepare("SELECT s.*, v.video_path FROM subtitles s JOIN videos v ON s.video_id = v.id WHERE s.id = ?");
                $stmt->execute([$subtitle_id]);
                $subtitle = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if (!$subtitle) {
                throw new Exception('Subtitle not found');
            }

            // Get video path for file-based system
            if ($conn === $this->db) {
                $video = $this->db->selectOne('videos', ['id' => $subtitle['video_id']]);
                $subtitle['video_path'] = $video['video_path'];
            }

            if (!$subtitle['translated_file_path']) {
                throw new Exception('Translated subtitle not found');
            }

            // Update status to processing
            $this->updateSubtitleStatus($subtitle_id, 'merge_status', 'processing');

            // Generate output filename
            $video_filename = basename($subtitle['video_path']);
            $video_name = pathinfo($video_filename, PATHINFO_FILENAME);
            $video_ext = pathinfo($video_filename, PATHINFO_EXTENSION);
            $merged_filename = $video_name . '_with_igbo_subs.' . $video_ext;
            $merged_path = $this->merged_dir . $merged_filename;

            // Check if FFmpeg is available (mock implementation)
            if (!$this->isFFmpegAvailable()) {
                // For demo purposes, just copy the original video
                if (!copy($subtitle['video_path'], $merged_path)) {
                    throw new Exception('Failed to create merged video');
                }
            } else {
                // Use FFmpeg to merge video with subtitles
                $subtitle_path = $subtitle['translated_file_path'];
                $video_path = $subtitle['video_path'];

                $command = "ffmpeg -i \"$video_path\" -vf \"subtitles=$subtitle_path\" -c:a copy \"$merged_path\"";

                exec($command, $output, $return_code);

                if ($return_code !== 0) {
                    throw new Exception('FFmpeg merge failed: ' . implode("\n", $output));
                }
            }

            // Update database with merged video path
            if ($conn === $this->db) {
                $this->db->update('subtitles',
                    ['merged_video_path' => $merged_path, 'merge_status' => 'completed'],
                    ['id' => $subtitle_id]
                );
            } else {
                $stmt = $conn->prepare("UPDATE subtitles SET merged_video_path = ?, merge_status = 'completed' WHERE id = ?");
                $stmt->execute([$merged_path, $subtitle_id]);
            }

            return $merged_path;

        } catch (Exception $e) {
            $this->updateSubtitleStatus($subtitle_id, 'merge_status', 'failed');
            throw new Exception('Video merge failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if FFmpeg is available
     */
    private function isFFmpegAvailable() {
        exec('ffmpeg -version 2>&1', $output, $return_code);
        return $return_code === 0;
    }

    /**
     * Update subtitle status
     */
    private function updateSubtitleStatus($subtitle_id, $status_field, $status) {
        $conn = $this->db->getConnection();

        if ($conn === $this->db) {
            $this->db->update('subtitles', [$status_field => $status], ['id' => $subtitle_id]);
        } else {
            $stmt = $conn->prepare("UPDATE subtitles SET $status_field = ? WHERE id = ?");
            $stmt->execute([$status, $subtitle_id]);
        }
    }

    /**
     * Get subtitle information
     */
    public function getSubtitleInfo($video_id) {
        $conn = $this->db->getConnection();

        if ($conn === $this->db) {
            return $this->db->selectOne('subtitles', ['video_id' => $video_id]);
        } else {
            $stmt = $conn->prepare("SELECT * FROM subtitles WHERE video_id = ?");
            $stmt->execute([$video_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}
?>
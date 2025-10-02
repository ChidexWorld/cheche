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

            // Validate file size (10MB max)
            $max_size = 10 * 1024 * 1024; // 10MB
            if ($subtitle_file['size'] > $max_size) {
                throw new Exception('Subtitle file too large. Maximum size is 10MB.');
            }

            // Generate unique filename
            $filename = 'subtitle_' . $video_id . '_' . time() . '.' . $file_extension;
            $file_path = $this->subtitle_dir . $filename;
            $relative_path = 'uploads/subtitles/' . $filename;

            // Move uploaded file
            if (!move_uploaded_file($subtitle_file['tmp_name'], $file_path)) {
                throw new Exception('Failed to upload subtitle file');
            }

            // Insert subtitle record
            $conn = $this->db->getConnection();
            if ($conn === $this->db) {
                // File-based database - use relative path
                $subtitle_id = $this->db->insert('subtitles', [
                    'video_id' => $video_id,
                    'original_file_path' => $relative_path,
                    'language_from' => 'en',
                    'language_to' => 'ig',
                    'translation_status' => 'pending',
                    'merge_status' => 'pending'
                ]);
            } else {
                // MySQL database - use relative path
                $stmt = $conn->prepare("INSERT INTO subtitles (video_id, original_file_path, language_from, language_to, translation_status, merge_status) VALUES (?, ?, 'en', 'ig', 'pending', 'pending')");
                $stmt->execute([$video_id, $relative_path]);
                $subtitle_id = $conn->lastInsertId();
            }

            return $subtitle_id;

        } catch (Exception $e) {
            throw new Exception('Subtitle upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Convert SRT to VTT format
     */
    public function convertSrtToVtt($srt_file_path, $vtt_file_path) {
        // Read SRT content
        if (!file_exists($srt_file_path)) {
            $base_dir = realpath(__DIR__ . '/../');
            $srt_file_path = $base_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $srt_file_path);
        }

        if (!file_exists($srt_file_path)) {
            throw new Exception('SRT file not found: ' . $srt_file_path);
        }

        $srt_content = file_get_contents($srt_file_path);

        // Convert SRT to VTT
        $vtt_content = "WEBVTT\n\n";

        // Replace comma with period in timestamps (SRT uses comma, VTT uses period)
        $vtt_content .= str_replace(',', '.', $srt_content);

        // Write VTT file
        if (file_put_contents($vtt_file_path, $vtt_content) === false) {
            throw new Exception('Failed to write VTT file');
        }

        return $vtt_file_path;
    }

    /**
     * Parse SRT subtitle file
     */
    public function parseSrtFile($file_path) {
        // Convert relative path to absolute if needed
        if (!file_exists($file_path)) {
            $base_dir = realpath(__DIR__ . '/../');
            $absolute_path = $base_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file_path);
            if (!file_exists($absolute_path)) {
                throw new Exception('Subtitle file not found. Expected: ' . $absolute_path . ' | Relative path: ' . $file_path);
            }
            $file_path = $absolute_path;
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
     * Translate text using English to Igbo dictionary
     * In production, you would integrate with Google Translate API or similar service
     */
    public function translateText($text, $from_lang = 'en', $to_lang = 'ig') {
        // Comprehensive English to Igbo dictionary
        $dictionary = [
            // Articles & Conjunctions (case-insensitive)
            'the' => 'nke', 'a' => 'otu', 'an' => 'otu', 'and' => 'na', 'or' => 'ma ọ bụ',
            'but' => 'mana', 'in' => 'na', 'on' => 'na', 'at' => 'na', 'to' => 'na',
            'for' => 'maka', 'of' => 'nke', 'with' => 'na', 'by' => 'site na', 'from' => 'si',

            // Pronouns & Determiners
            'i' => 'm', 'you' => 'gị', 'he' => 'ya', 'she' => 'ya', 'it' => 'ya',
            'we' => 'anyị', 'they' => 'ha', 'this' => 'nke a', 'that' => 'nke ahụ',
            'these' => 'ndị a', 'those' => 'ndị ahụ', 'my' => 'nke m', 'your' => 'nke gị',
            'his' => 'nke ya', 'her' => 'nke ya', 'our' => 'nke anyị', 'their' => 'nke ha',

            // Verbs
            'is' => 'bụ', 'was' => 'bụ', 'are' => 'bụ', 'were' => 'bụ', 'be' => 'bụ',
            'have' => 'nwere', 'has' => 'nwere', 'had' => 'nwere', 'do' => 'mee',
            'does' => 'na-eme', 'did' => 'mere', 'will' => 'ga', 'would' => 'ga',
            'can' => 'nwere ike', 'could' => 'nwere ike', 'should' => 'kwesịrị',
            'must' => 'ga-', 'may' => 'nwere ike', 'might' => 'nwere ike',

            // Education & Learning
            'learning' => 'mmụta', 'elearning' => 'mmụta elektrọnik', 'education' => 'agụmakwụkwọ',
            'platform' => 'ikpo okwu', 'system' => 'usoro', 'course' => 'ọmụmụ ihe',
            'courses' => 'ọmụmụ ihe', 'video' => 'vidiyo', 'videos' => 'vidiyo',
            'student' => 'nwa akwụkwọ', 'students' => 'ụmụ akwụkwọ', 'learner' => 'onye na-amụ',
            'learners' => 'ndị na-amụ', 'instructor' => 'onye nkuzi', 'instructors' => 'ndị nkuzi',
            'teacher' => 'onye nkuzi', 'teachers' => 'ndị nkuzi', 'lesson' => 'ihe nkuzi',
            'lessons' => 'ihe nkuzi', 'study' => 'mụọ ihe', 'learn' => 'mụta', 'teach' => 'kuziere',
            'knowledge' => 'ihe ọmụma', 'skill' => 'nka', 'skills' => 'nka',

            // Technology & Platform
            'online' => 'n\'ịntanetị', 'content' => 'ọdịnaya', 'module' => 'modul',
            'assignment' => 'ọrụ enyere', 'quiz' => 'ajụjụ ule', 'test' => 'ule',
            'exam' => 'ule', 'certificate' => 'asambodo', 'design' => 'imepụta',
            'implement' => 'mejuputa', 'develop' => 'mepụta', 'developed' => 'mepụtara',
            'create' => 'kee', 'build' => 'wuo', 'modern' => 'ọgbara ọhụrụ',
            'scalable' => 'nke nwere ike ịgbatị', 'user-friendly' => 'dị mfe iji',
            'tools' => 'ngwá ọrụ', 'manage' => 'jikwaa', 'monitor' => 'nyochaa',
            'upload' => 'bulite', 'download' => 'budata', 'access' => 'nweta',
            'provide' => 'nye', 'provided' => 'nyere', 'support' => 'nkwado',
            'feature' => 'njirimara', 'features' => 'njirimara', 'interface' => 'ihu',

            // Objectives & Goals
            'objective' => 'ebumnuche', 'objectives' => 'ebumnuche', 'main' => 'isi',
            'proposed' => 'tụrụ anya', 'model' => 'ụdị', 'particularly' => 'karịsịa',
            'specifically' => 'kpọmkwem', 'addressed' => 'lebara anya',
            'engagement' => 'ntinye aka', 'limitations' => 'mmachi', 'limitation' => 'mmachi',
            'existing' => 'nke dị ugbu a', 'context' => 'ọnọdụ',

            // Actions
            'click' => 'pịa', 'select' => 'họrọ', 'choose' => 'họrọ', 'save' => 'chekwaa',
            'delete' => 'hichapụ', 'edit' => 'dezie', 'update' => 'melite', 'submit' => 'ziga',
            'cancel' => 'kagbuo', 'confirm' => 'kwado', 'search' => 'chọọ',
            'view' => 'lelee', 'watch' => 'lelee', 'play' => 'kpọọ', 'pause' => 'kwụsịtụ',

            // Common Words
            'hello' => 'ndewo', 'welcome' => 'nnọọ', 'thank' => 'daalụ', 'thanks' => 'daalụ',
            'please' => 'biko', 'yes' => 'ee', 'no' => 'mba', 'ok' => 'ọ dị mma',
            'good' => 'ọma', 'bad' => 'ọjọọ', 'new' => 'ọhụrụ', 'old' => 'ochie',
            'first' => 'mbụ', 'last' => 'ikpeazụ', 'next' => 'ọzọ', 'previous' => 'gara aga',
            'all' => 'niile', 'some' => 'ụfọdụ', 'more' => 'ọzọ', 'less' => 'obere',

            // Nigerian Context
            'nigerian' => 'Naịjịrịa', 'nigeria' => 'Naịjịrịa', 'udeme' => 'Udeme',
        ];

        // Convert text to lowercase for matching, preserve original case pattern
        $words = preg_split('/(\s+)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $translated_words = [];

        foreach ($words as $word) {
            // Skip whitespace
            if (preg_match('/^\s+$/', $word)) {
                $translated_words[] = $word;
                continue;
            }

            // Extract punctuation
            preg_match('/^([^\w]*)(.+?)([^\w]*)$/u', $word, $matches);
            $prefix = $matches[1] ?? '';
            $core_word = $matches[2] ?? $word;
            $suffix = $matches[3] ?? '';

            // Try to translate
            $lower_word = strtolower($core_word);
            if (isset($dictionary[$lower_word])) {
                $translated_words[] = $prefix . $dictionary[$lower_word] . $suffix;
            } else {
                $translated_words[] = $word;
            }
        }

        return implode('', $translated_words);
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

            // Save translated subtitle file (SRT)
            $translated_filename = 'subtitle_translated_' . $subtitle_id . '_' . time() . '.srt';
            $translated_path_absolute = $this->subtitle_dir . $translated_filename;
            $translated_path_relative = 'uploads/subtitles/' . $translated_filename;

            $this->saveSrtFile($translated_path_absolute, $translated_subtitles);

            // Convert to VTT for HTML5 video
            $vtt_filename = 'subtitle_translated_' . $subtitle_id . '_' . time() . '.vtt';
            $vtt_path_absolute = $this->subtitle_dir . $vtt_filename;
            $vtt_path_relative = 'uploads/subtitles/' . $vtt_filename;

            try {
                $this->convertSrtToVtt($translated_path_absolute, $vtt_path_absolute);
                // Use VTT path as the primary translated file path
                $translated_path_relative = $vtt_path_relative;
            } catch (Exception $e) {
                error_log('VTT conversion failed: ' . $e->getMessage());
                // Fall back to SRT if VTT conversion fails
            }

            // Update database with translated file path (use relative path)
            if ($conn === $this->db) {
                $this->db->update('subtitles',
                    ['translated_file_path' => $translated_path_relative, 'translation_status' => 'completed'],
                    ['id' => $subtitle_id]
                );
            } else {
                $stmt = $conn->prepare("UPDATE subtitles SET translated_file_path = ?, translation_status = 'completed' WHERE id = ?");
                $stmt->execute([$translated_path_relative, $subtitle_id]);
            }

            return $translated_path_relative;

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

            // Convert relative paths to absolute for file operations
            // Normalize the path by using realpath on the base directory
            $base_dir = realpath(__DIR__ . '/../');
            $video_path_absolute = $base_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subtitle['video_path']);
            $subtitle_path_absolute = $base_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subtitle['translated_file_path']);

            // Generate output filename
            $video_filename = basename($subtitle['video_path']);
            $video_name = pathinfo($video_filename, PATHINFO_FILENAME);
            $video_ext = pathinfo($video_filename, PATHINFO_EXTENSION);
            $merged_filename = $video_name . '_with_igbo_subs.' . $video_ext;
            $merged_path_absolute = $this->merged_dir . $merged_filename;
            $merged_path_relative = 'uploads/merged_videos/' . $merged_filename;

            // Verify files exist before processing
            if (!file_exists($video_path_absolute)) {
                throw new Exception('Original video file not found. Expected: ' . $video_path_absolute . ' | Database path: ' . $subtitle['video_path']);
            }
            if (!file_exists($subtitle_path_absolute)) {
                throw new Exception('Translated subtitle file not found. Expected: ' . $subtitle_path_absolute);
            }

            // Check if FFmpeg is available
            if (!$this->isFFmpegAvailable()) {
                // For demo purposes, just copy the original video
                if (!copy($video_path_absolute, $merged_path_absolute)) {
                    throw new Exception('Failed to create merged video');
                }
            } else {
                // Use FFmpeg to merge video with subtitles
                $command = sprintf(
                    'ffmpeg -i "%s" -vf "subtitles=%s" -c:a copy "%s" 2>&1',
                    $video_path_absolute,
                    str_replace('\\', '/', $subtitle_path_absolute),
                    $merged_path_absolute
                );

                exec($command, $output, $return_code);

                if ($return_code !== 0) {
                    throw new Exception('FFmpeg merge failed: ' . implode("\n", $output));
                }
            }

            // Update database with merged video path (use relative path)
            if ($conn === $this->db) {
                $this->db->update('subtitles',
                    ['merged_video_path' => $merged_path_relative, 'merge_status' => 'completed'],
                    ['id' => $subtitle_id]
                );
            } else {
                $stmt = $conn->prepare("UPDATE subtitles SET merged_video_path = ?, merge_status = 'completed' WHERE id = ?");
                $stmt->execute([$merged_path_relative, $subtitle_id]);
            }

            return $merged_path_relative;

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
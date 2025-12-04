<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// --- AUTO DELETE (Garbage Collection) ---
// Deletes files older than 10 minutes
$download_dir = __DIR__ . '/downloads/';
if (!is_dir($download_dir)) mkdir($download_dir, 0755, true);

$files = glob($download_dir . '*');
$now = time();
if ($files) {
    foreach ($files as $file) {
        if (is_file($file) && ($now - filemtime($file) >= 600)) {
            unlink($file);
        }
    }
}
// ----------------------------------------

$url = isset($_GET['url']) ? $_GET['url'] : '';
$quality = isset($_GET['quality']) ? $_GET['quality'] : ''; // Optional: "1080", "720"

if (!$url) {
    echo json_encode(["status" => "error", "message" => "Please provide a YouTube URL"]);
    exit;
}

// Run the Python script
$command = "python3 yt.py " . escapeshellarg($url) . " " . escapeshellarg($quality) . " 2>&1";
$output = shell_exec($command);

// Output Handling
if ($output) {
    $json = json_decode($output, true);
    
    if (isset($json['status']) && $json['status'] === 'success') {
        // Build the download link
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $domain = $_SERVER['HTTP_HOST'];
        $file_url = "$protocol://$domain/downloads/" . $json['filename'];

        echo json_encode([
            "status" => "success",
            "title" => $json['title'],
            "quality" => $json['quality'],
            "download_link" => $file_url,
            "thumbnail" => $json['thumbnail']
        ]);
    } else {
        // If an error occurred (like YouTube blocking IP)
        echo $output;
    }
} else {
    echo json_encode(["status" => "error", "message" => "Server execution failed"]);
}
?>

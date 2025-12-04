<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// --- AUTO DELETE (Garbage Collection) ---
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
$quality = isset($_GET['quality']) ? $_GET['quality'] : ''; 

if (!$url) {
    echo json_encode(["status" => "error", "message" => "Please provide a YouTube URL"]);
    exit;
}

// Run Python
$command = "python3 yt.py " . escapeshellarg($url) . " " . escapeshellarg($quality) . " 2>&1";
$output = shell_exec($command);

if ($output) {
    // FIX: Extract only the JSON part from the output
    // This finds the first "{" and grabs everything after it
    $jsonStart = strpos($output, '{');
    if ($jsonStart !== false) {
        $jsonString = substr($output, $jsonStart);
        $json = json_decode($jsonString, true);
        
        if (isset($json['status']) && $json['status'] === 'success') {
            // Build the Link
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
            $domain = $_SERVER['HTTP_HOST'];
            $file_url = "$protocol://$domain/downloads/" . $json['filename'];

            echo json_encode([
                "status" => "success",
                "title" => $json['title'],
                "quality" => $json['quality'],
                "download_link" => $file_url, // <-- HERE IS YOUR LINK
                "thumbnail" => $json['thumbnail']
            ]);
        } else {
            // If Python reported an error inside the JSON
            echo $jsonString;
        }
    } else {
        // If no JSON was found at all (Hard Crash)
        echo json_encode(["status" => "error", "raw_output" => $output]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Server execution failed"]);
}
?>

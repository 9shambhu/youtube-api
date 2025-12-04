import sys
import yt_dlp
import json
import os

# 1. Check for URL
if len(sys.argv) < 2:
    print(json.dumps({"status": "error", "message": "No URL provided"}))
    sys.exit()

url = sys.argv[1]
quality = sys.argv[2] if len(sys.argv) > 2 else None  # e.g., "1080" or "720"

output_folder = "/var/www/html/downloads"
cookie_file = "/var/www/html/cookies.txt" # It looks for this file automatically

# 2. Define Quality Logic
# If user asks for 720, we get best video <= 720p. If not, we get absolute best.
if quality:
    format_string = f'bestvideo[height<={quality}]+bestaudio/best[height<={quality}]'
else:
    format_string = 'bestvideo+bestaudio/best'

ydl_opts = {
    'quiet': True,
    'no_warnings': True,
    'format': format_string,
    'merge_output_format': 'mp4',
    'outtmpl': f'{output_folder}/%(id)s.%(ext)s',
    'max_filesize': 500 * 1024 * 1024, # Limit to 500MB
    
    # Fake a Windows User Agent to trick YouTube
    'user_agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
    
    # Only use cookies if you uploaded the file
    'cookiefile': cookie_file if os.path.exists(cookie_file) else None,
}

try:
    with yt_dlp.YoutubeDL(ydl_opts) as ydl:
        info = ydl.extract_info(url, download=True)
        
        filename = f"{info['id']}.mp4"

        print(json.dumps({
            "status": "success",
            "title": info.get('title'),
            "filename": filename,
            "duration": info.get('duration'),
            "thumbnail": info.get('thumbnail'),
            "quality": f"{info.get('height')}p"
        }))

except Exception as e:
    print(json.dumps({"status": "error", "message": str(e)}))

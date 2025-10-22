String Analyzer API

A simple PHP-based API that analyzes strings, saves them to a SQLite database, and allows retrieval, filtering, and deletion.

✅ Project Setup
📁 Folder Structure
/ (public_html or project root)
│── index.php                 → Main entry point (routing + controllers in one file)
│── .htaccess                 → Enables clean URL routing
│── /src
│     └── /config
│            └── database.php → Handles SQLite database connection & table creation
│── /data
│     └── strings.sqlite      → Auto-created database file

⚙️ Requirements

PHP 8.0+

PDO SQLite extension enabled

Apache server with .htaccess rewrite support (for Pxxl, 000webhost, InfinityFree, CPanel, etc.)

🚀 Installation & Running Locally

Clone the repository

git clone <your-repo-url>
cd your-project-folder


Start PHP’s development server

php -S localhost:8000


Access API

POST    http://localhost:8000/strings
GET     http://localhost:8000/strings
GET     http://localhost:8000/strings/{value}
DELETE  http://localhost:8000/strings/{value}
GET     http://localhost:8000/strings/filter-by-natural-language?query=palindrome

📂 Important File: .htaccess

This file must be in the root or public_html folder:

Options +FollowSymLinks
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

RewriteRule ^(.*)$ index.php [QSA,L]


✔ This ensures all requests like /strings, /strings/hello, /strings/filter-by-natural-language go to index.php and don’t return 404.

🗄️ Database Setup

No manual setup required.

SQLite database file: /data/strings.sqlite

Automatically created if missing

Handled by src/config/database.php

You should commit the /data folder if you want to keep sample data in the repo.

✅ Endpoints
Method	Endpoint	Description
POST	/strings	Create & analyze a string
GET	/strings	Get all saved strings
GET	/strings/{value}	Retrieve a single string
GET	/strings/filter-by-natural-language?query=	Natural language filtering
DELETE	/strings/{value}	Delete a string from database
Sample Request: Create a String
POST /strings
Content-Type: application/json

{
  "value": "racecar"
}

🌐 Deploying to Hosting (Pxxl, CPanel, 000webhost, etc.)

✔ Upload all files to public_html/
✔ Ensure .htaccess is in the root (not inside /src)
✔ PHP version must be 8.0+
✔ SQLite is stored in /data/strings.sqlite (auto-created)
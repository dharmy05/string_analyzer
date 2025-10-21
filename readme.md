📘 String Analyzer API

A simple RESTful API that analyzes strings and stores their properties in a SQLite database.
It can check if a string is a palindrome, count unique characters, generate SHA-256 hashes, calculate word count, store character frequency, and more.

🚀 Features

✅ Analyze string properties:

Length

Palindrome check

Unique characters

Word count

Character frequency map

SHA-256 hash

✅ Store analyzed strings in a database

✅ Prevent duplicate string entries

✅ Filter strings (query params & natural language)

✅ Retrieve, delete, and list strings

✅ Built using PHP + SQLite (PDO)

📂 Project Structure
project/
├── config/
│   └── database.php          # Database connection using PDO SQLite
├── src/
│   ├── controllers/          # Request handlers (create, get, delete, filter)
│   └── services/stringServices.php  # Logic for analyzing strings
├── database/ (optional)
│   └── strings.db            # SQLite database file (not tracked on GitHub)
├── index.php                 # Route entry point
└── README.md                 # You are here

⚙️ Requirements

PHP 8+

SQLite (bundled in PHP)

XAMPP, WAMP, Laragon, or any PHP server

Postman / cURL for API testing


🛠️ Setup & Run Locally

1. Clone the repo
git clone <your-repository-url>
cd project

2. Ensure database exists

If strings.db is not included, create it automatically by running the app OR manually using SQLite:

CREATE TABLE IF NOT EXISTS strings (
    id TEXT PRIMARY KEY,
    value TEXT NOT NULL,
    length INTEGER,
    is_palindrome INTEGER,
    unique_characters INTEGER,
    word_count INTEGER,
    sha256_hash TEXT,
    character_frequency_map TEXT,
    created_at TEXT
);

3. Start PHP server
php -S localhost:8000


Or, if using XAMPP, place project inside htdocs and visit:

http://localhost/your-project-folder

📡 API Endpoints
Method	Endpoint	Description
POST	/string	Analyze and store a new string
GET	/string/{value}	Retrieve analyzed string by value
GET	/strings	Get all stored strings (supports filters)
DELETE	/string/{value}	Delete a string by its SHA-256 hash
GET	/strings/natural?query=...	Filter using natural language (e.g. "palindromes longer than 5")
✅ Example Requests
➤ POST /string
{
  "value": "racecar"
}


Response:

{
  "id": "c8d...hash",
  "value": "racecar",
  "properties": {
    "length": 7,
    "is_palindrome": true,
    "unique_characters": 5,
    "word_count": 1,
    "sha256_hash": "c8d..."
  },
  "created_at": "2025-01-01T00:00:00Z"
}

➤ DELETE /string/racecar
curl -X DELETE http://localhost:8000/string/racecar


Response:

204 No Content

🌐 Deployment Notes

The database.db file is usually not pushed to GitHub.

On the server:

Create the database file manually or auto-generate on first request.

Ensure write permissions (chmod 666 database.db on Linux or proper folder permissions on Windows/XAMPP).

Update database.php to use the correct path:

$dbPath = __DIR__ . '/../database/strings.db';
$conn = new PDO("sqlite:$dbPath");

✅ Future Improvements

✅ Add migrations to auto-create tables

✅ Use environment variables for DB path

🚀 Add unit tests

🚀 Add Docker support

🚀 Add authentication (API keys / JWT)


👨‍💻 Author
Oluwadamilola olaleye
Feel free to fork, improve, and contribute ❤️
LeafSense - Full project package

Run steps:
1. Copy folder to your web server root (Wamp: C:\wamp64\www\leafsense)
2. Create uploads folder and make writable.
3. Import sql/schema.sql into MySQL (phpMyAdmin).
4. Edit db.php with your DB credentials.
5. (Optional) Setup Python venv: cd python; python -m venv venv; venv\Scripts\activate; pip install -r requirements.txt
6. Run Flask model server (optional): python model_server.py
7. Open http://localhost/leafsense/index.html

Notes:
- If shell_exec is disabled on Windows, use Flask model server (recommended).
- train.py is a transfer learning skeleton; prepare dataset folder with subfolders per class.

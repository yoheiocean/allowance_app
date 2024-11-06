# Allowance Investment Web App
## About this app
It is a simple web app, running in a Docker container, where my two children can use in lieu of their phyiscal bankbooks to keep track of their allowance. It mimics an investment account where the balance grows over time with manually-triggerred interest. It teaches them the importance of saving and investing.

## Dependencies
- Docker
- SQLite

## Installation
### 1. Install SQLite on the host machine
```bash
sudo apt update
sudo apt install sqlite3
```


### 2. Create a database in `web/`.
```bash
sqlite3 allowance_tracker.db
# sudo if necessary
```

### 3. Create a table
```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    balance REAL DEFAULT 0,
    last_interest_applied TEXT
);

CREATE TABLE transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    amount REAL,
    transaction_type TEXT,
    description TEXT,
    timestamp TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Inserting two sample users
INSERT INTO users (id, name) VALUES (1, 'Child1');
INSERT INTO users (id, name) VALUES (2, 'Child2');

```
*Change `Child1` and `Child2` to the actual names.*

To verify the database:
```sql
.tables
PRAGMA table_info(users);
PRAGMA table_info(transactions);
.quit

```

### 4. Change the permission of the database (and all other web files in `web/`)
```bash
sudo chown -R www-data:www-data /path/to/web
sudo chmod -R 775 /path/to/web
```

### 5. Start the Docker container
```bash
docker-compose up -d
```

### 6. Revise some files
1. `index.html` -- Change `child1` and `child2` to actual names
2. `admin_panel.php` -- Change the password, `child1` and `child2`
3. `dashboard.php` -- Change `child1` and `child2`.

### 7. Access the server
- `localhost:16510` or any port that you define in `docker-compose.yml`.
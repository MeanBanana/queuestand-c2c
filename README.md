Users
```
CREATE TABLE users (
    user_id INT(13) PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(10),
    role ENUM('job_poster', 'queue_stander', 'admin') NOT NULL DEFAULT 'job_poster',
    is_verified TINYINT(1) DEFAULT 0,
    city VARCHAR(100) DEFAULT 'Johannesburg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

```

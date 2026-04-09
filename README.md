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
Jobs
```
CREATE TABLE jobs (
    job_id INT AUTO_INCREMENT PRIMARY KEY,
    poster_id VARCHAR(13) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    location VARCHAR(255) NOT NULL,
    required_datetime DATETIME NOT NULL,
    pay_amount DECIMAL(10,2) NOT NULL,
    status ENUM('open','assigned','in_progress','completed','cancelled') DEFAULT 'open',
    assigned_stander_id VARCHAR(13) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (poster_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_stander_id) REFERENCES users(user_id) ON DELETE SET NULL
);
```
Reveiws
```
CREATE TABLE reviews (
    review_id     INT AUTO_INCREMENT PRIMARY KEY,
    job_id        INT NOT NULL,
    rater_id      VARCHAR(13) NOT NULL,        -- who gave the rating
    rated_id      VARCHAR(13) NOT NULL,        -- who received the rating
    rating        TINYINT(1) NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment       TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (job_id)   REFERENCES jobs(job_id) ON DELETE CASCADE,
    FOREIGN KEY (rater_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (rated_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

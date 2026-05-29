# QueueStand

A platform where users can post queue jobs or stand in queues on behalf of others. All users share the same role and can do both — only admins have access to the admin portal.

---

## Database Schema

### Users
```sql
CREATE TABLE users (
    user_id INT(13) PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(10),
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    is_verified TINYINT(1) DEFAULT 0,
    city VARCHAR(100) DEFAULT 'Johannesburg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Jobs
```sql
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

### Reviews
```sql
CREATE TABLE reviews (
    review_id     INT AUTO_INCREMENT PRIMARY KEY,
    job_id        INT NOT NULL,
    rater_id      VARCHAR(13) NOT NULL,
    rated_id      VARCHAR(13) NOT NULL,
    rating        TINYINT(1) NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment       TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (job_id)   REFERENCES jobs(job_id) ON DELETE CASCADE,
    FOREIGN KEY (rater_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (rated_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

### Transactions
```sql
CREATE TABLE transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','paid','released','refunded') DEFAULT 'pending',
    payment_gateway VARCHAR(50) DEFAULT 'PayFast',
    gateway_tx_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE
);
```

---

## Roles

| Role    | Description                                      |
|---------|--------------------------------------------------|
| `user`  | Can post jobs and/or stand in queues             |
| `admin` | Full access to the admin portal                  |

---

## Pages

| Page                        | Access        |
|-----------------------------|---------------|
| `/register.php`             | Public        |
| `/login.php`                | Public        |
| `/dashboard.php`            | Logged in     |
| `/post-job.php`             | Logged in     |
| `/browse-jobs.php`          | Logged in     |
| `/profile.php`              | Logged in     |
| `/admin/admin-dashboard.php`| Admin only    |
| `/admin/admin-users.php`    | Admin only    |
| `/admin/admin-jobs.php`     | Admin only    |
| `/admin/admin-login.php`    | Public        |

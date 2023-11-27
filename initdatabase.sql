CREATE TABLE roles(
    id INT AUTO_INCREMENT PRIMARY KEY,
    role VARCHAR (50) NOT NULL
);

INSERT INTO roles (role) VALUES
    ("admin"),
    ("registered"),
    ("broker"),
    ("guest");

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role INT NOT NULL,
    FOREIGN KEY (role) REFERENCES roles(id) ON DELETE CASCADE
);

INSERT INTO users (username, password, role) VALUES
    ("admin","admin",1),
    ("registered","registered",2),
    ("broker","broker",3),
    ("guest","guest",4);

-- TODO remove
INSERT INTO users (username, password, role) VALUES ("xdohna52","xdohna52",2);

CREATE TABLE IF NOT EXISTS systems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    admin_id INT NOT NULL,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO systems (name, description, admin_id) VALUES
    ("Linka A7", "Výroba šroubů", 1),
    ("Linka B5", "Výroba matic", 2)

CREATE TABLE IF NOT EXISTS devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_name VARCHAR(255) NOT NULL,
    device_type VARCHAR(255) NOT NULL,
    device_description TEXT,
    user_alias VARCHAR(255),
    hodnota FLOAT, -- Teplota senzoru (příklad)
    jednotka VARCHAR(255),
    maintenance_interval INT -- Interval údržby v dnech (příklad)
);

INSERT INTO devices (device_name, device_type, device_description, hodnota, jednotka) VALUES
    ("AE8B", "Thermometer", "", 30, "C"),
    ("ECP7", "Thermometer", "", 29, "C"),
    ("WDD1", "Hygrometer", "", 45, "%"),
    ("XP8E", "Barometer", "", 1013, "hPa");


CREATE TABLE IF NOT EXISTS system_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    system_id INT NOT NULL,
    device_id INT NOT NULL,
    FOREIGN KEY (system_id) REFERENCES systems(id) ON DELETE CASCADE,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
);

INSERT INTO system_devices (system_id, device_id) VALUES
    (1,1),
    (1,3),
    (2,2),
    (2,4);


CREATE TABLE IF NOT EXISTS compare(
    id INT AUTO_INCREMENT PRIMARY KEY,
    typ VARCHAR(20) NOT NULL
);

INSERT INTO compare (typ) VALUES
    ("=="),
    ("!="),
    ("<"),
    ("<="),
    (">"),
    (">=");

CREATE TABLE IF NOT EXISTS KPI(
    id INT AUTO_INCREMENT PRIMARY KEY,
    val FLOAT NOT NULL,
    device_id INT NOT NULL, 
    system_id INT NOT NULL,
    typ INT NOT NULL,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
    FOREIGN KEY (system_id) REFERENCES systems(id) ON DELETE CASCADE,
    FOREIGN KEY (typ) REFERENCES compare(id) ON DELETE CASCADE
);

INSERT INTO KPI (val, device_id, system_id, typ) VALUES
    (60, 1, 1, 3),
    (70, 3, 1, 4),
    (60, 2, 2, 3),
    (900, 4, 2, 6);

CREATE TABLE IF NOT EXISTS system_access_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    system_id INT NOT NULL,
    requesting_user_id INT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    request_date DATETIME NOT NULL,
    FOREIGN KEY (system_id) REFERENCES systems(id),
    FOREIGN KEY (requesting_user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS system_user_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    system_id INT NOT NULL,
    user_id INT NOT NULL,
    access_granted_date DATETIME NOT NULL,
    FOREIGN KEY (system_id) REFERENCES systems(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
create table users(
id int auto_increment primary key,
email varchar(255) not null unique,
password varchar(255) not null,
created_at timestamp default current_timestamp,
updated_at timestamp default now() on update now()
);
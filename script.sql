DROP DATABASE IF EXISTS social_network;
CREATE DATABASE social_network CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
use social_network;

create table user(
id int not null primary key auto_increment,
firstname varchar(50) not null,
lastname varchar(50) not null,
email varchar(100) not null,
pass char(60) not null
)engine=InnoDB;

create unique index ix1 on user(email);


create table post(
id int not null primary key auto_increment,
content text,
user int not null,
date datetime not null default now()
)engine=InnoDB;

create table comment(
id int not null primary key auto_increment,
user int not null,
post int not null,
content text not null,
date datetime not null default now()
)engine=InnoDB;

create table likes(
id int not null primary key auto_increment,
user int not null,
post int not null
)engine=InnoDB;




alter table post add FOREIGN KEY (user) REFERENCES user(id);

alter table comment add FOREIGN KEY (user) REFERENCES user(id);
alter table comment add FOREIGN KEY (post) REFERENCES post(id);

alter table likes add FOREIGN KEY (user) REFERENCES user(id);
alter table likes add FOREIGN KEY (post) REFERENCES post(id);


insert into user (id,firstname,lastname,email,pass) values
(null,'Test1','Last1','mail1@gmail.com','$2y$10$LFXuW6y.P0Zd81fwd..CK.pCd6ZcoT5DsY7rqet9jwzReaoRi7yua');

insert into user (firstname,lastname,email,pass) values
('Test2','Last2','mail2@gmail.com','$2y$10$LFXuW6y.P0Zd81fwd..CK.pCd6ZcoT5DsY7rqet9jwzReaoRi7yua');


insert into post (content,user) values ('Evo danas pada kiša opet :(',1), ('Jedem jagode.',2);



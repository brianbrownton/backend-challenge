DROP DATABASE IF EXISTS `everlywell`;

CREATE DATABASE `everlywell` DEFAULT CHARACTER SET utf8mb4;

USE `everlywell`;

create table if not exists MemberFriends
(
	MemberFriendsId int auto_increment
		primary key,
	FirstMemberId int null,
	SecondMemberId int null
);

create table if not exists MemberHeadings
(
	MemberHeadingsId int auto_increment
		primary key,
	MemberId int null,
	HeadingType varchar(255) null,
	Heading varchar(16000) null
);

create table if not exists Members
(
	MemberId int auto_increment
		primary key,
	Name varchar(255) null,
	WebsiteUrl varchar(255) null,
	WebsiteUrlShortened varchar(255) null
);


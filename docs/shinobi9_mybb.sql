-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 04, 2026 at 08:24 PM
-- Server version: 5.7.44-48
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shinobi9_mybb`
--

-- --------------------------------------------------------

--
-- Table structure for table `mybb_audit_prueba`
--

CREATE TABLE `mybb_audit_prueba` (
  `user_id` int(11) NOT NULL,
  `user_moderador` varchar(2500) COLLATE utf8_unicode_ci NOT NULL,
  `current_moderador` varchar(2500) COLLATE utf8_unicode_ci NOT NULL,
  `old_ph` int(11) NOT NULL,
  `new_ph` int(11) NOT NULL,
  `old_ryos` int(11) NOT NULL,
  `new_ryos` int(11) NOT NULL,
  `fecha` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_adminlog`
--

CREATE TABLE `mybb_sg_adminlog` (
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `ipaddress` varbinary(16) NOT NULL DEFAULT '',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `module` varchar(50) NOT NULL DEFAULT '',
  `action` varchar(50) NOT NULL DEFAULT '',
  `data` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_adminoptions`
--

CREATE TABLE `mybb_sg_adminoptions` (
  `uid` int(11) NOT NULL DEFAULT '0',
  `cpstyle` varchar(50) NOT NULL DEFAULT '',
  `cplanguage` varchar(50) NOT NULL DEFAULT '',
  `codepress` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text NOT NULL,
  `permissions` text NOT NULL,
  `defaultviews` text NOT NULL,
  `loginattempts` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `loginlockoutexpiry` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `authsecret` varchar(16) NOT NULL DEFAULT '',
  `recovery_codes` varchar(177) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_adminsessions`
--

CREATE TABLE `mybb_sg_adminsessions` (
  `sid` varchar(32) NOT NULL DEFAULT '',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `loginkey` varchar(50) NOT NULL DEFAULT '',
  `ip` varbinary(16) NOT NULL DEFAULT '',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `lastactive` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `data` text NOT NULL,
  `useragent` varchar(200) NOT NULL DEFAULT '',
  `authenticated` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_adminviews`
--

CREATE TABLE `mybb_sg_adminviews` (
  `vid` int(10) UNSIGNED NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `title` varchar(100) NOT NULL DEFAULT '',
  `type` varchar(6) NOT NULL DEFAULT '',
  `visibility` tinyint(1) NOT NULL DEFAULT '0',
  `fields` text NOT NULL,
  `conditions` text NOT NULL,
  `custom_profile_fields` text NOT NULL,
  `sortby` varchar(20) NOT NULL DEFAULT '',
  `sortorder` varchar(4) NOT NULL DEFAULT '',
  `perpage` smallint(4) UNSIGNED NOT NULL DEFAULT '0',
  `view_type` varchar(6) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_announcements`
--

CREATE TABLE `mybb_sg_announcements` (
  `aid` int(10) UNSIGNED NOT NULL,
  `fid` int(11) NOT NULL DEFAULT '0',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `subject` varchar(120) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `startdate` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `enddate` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `allowhtml` tinyint(1) NOT NULL DEFAULT '0',
  `allowmycode` tinyint(1) NOT NULL DEFAULT '0',
  `allowsmilies` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_attachments`
--

CREATE TABLE `mybb_sg_attachments` (
  `aid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `posthash` varchar(50) NOT NULL DEFAULT '',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `filename` varchar(255) NOT NULL DEFAULT '',
  `filetype` varchar(120) NOT NULL DEFAULT '',
  `filesize` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `attachname` varchar(255) NOT NULL DEFAULT '',
  `downloads` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `dateuploaded` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `visible` tinyint(1) NOT NULL DEFAULT '0',
  `thumbnail` varchar(120) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_attachtypes`
--

CREATE TABLE `mybb_sg_attachtypes` (
  `atid` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL DEFAULT '',
  `mimetype` varchar(120) NOT NULL DEFAULT '',
  `extension` varchar(10) NOT NULL DEFAULT '',
  `maxsize` int(15) UNSIGNED NOT NULL DEFAULT '0',
  `icon` varchar(100) NOT NULL DEFAULT '',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `forcedownload` tinyint(1) NOT NULL DEFAULT '0',
  `groups` text NOT NULL,
  `forums` text NOT NULL,
  `avatarfile` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_awaitingactivation`
--

CREATE TABLE `mybb_sg_awaitingactivation` (
  `aid` int(10) UNSIGNED NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `code` varchar(100) NOT NULL DEFAULT '',
  `type` char(1) NOT NULL DEFAULT '',
  `validated` tinyint(1) NOT NULL DEFAULT '0',
  `misc` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_badwords`
--

CREATE TABLE `mybb_sg_badwords` (
  `bid` int(10) UNSIGNED NOT NULL,
  `badword` varchar(100) NOT NULL DEFAULT '',
  `regex` tinyint(1) NOT NULL DEFAULT '0',
  `replacement` varchar(100) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_banfilters`
--

CREATE TABLE `mybb_sg_banfilters` (
  `fid` int(10) UNSIGNED NOT NULL,
  `filter` varchar(200) NOT NULL DEFAULT '',
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `lastuse` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_banned`
--

CREATE TABLE `mybb_sg_banned` (
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `gid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `oldgroup` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `oldadditionalgroups` text NOT NULL,
  `olddisplaygroup` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `admin` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `bantime` varchar(50) NOT NULL DEFAULT '',
  `lifted` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `reason` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_buddyrequests`
--

CREATE TABLE `mybb_sg_buddyrequests` (
  `id` int(10) UNSIGNED NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `touid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_calendarpermissions`
--

CREATE TABLE `mybb_sg_calendarpermissions` (
  `cid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `gid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `canviewcalendar` tinyint(1) NOT NULL DEFAULT '0',
  `canaddevents` tinyint(1) NOT NULL DEFAULT '0',
  `canbypasseventmod` tinyint(1) NOT NULL DEFAULT '0',
  `canmoderateevents` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_calendars`
--

CREATE TABLE `mybb_sg_calendars` (
  `cid` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT '',
  `disporder` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `startofweek` tinyint(1) NOT NULL DEFAULT '0',
  `showbirthdays` tinyint(1) NOT NULL DEFAULT '0',
  `eventlimit` smallint(3) UNSIGNED NOT NULL DEFAULT '0',
  `moderation` tinyint(1) NOT NULL DEFAULT '0',
  `allowhtml` tinyint(1) NOT NULL DEFAULT '0',
  `allowmycode` tinyint(1) NOT NULL DEFAULT '0',
  `allowimgcode` tinyint(1) NOT NULL DEFAULT '0',
  `allowvideocode` tinyint(1) NOT NULL DEFAULT '0',
  `allowsmilies` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_captcha`
--

CREATE TABLE `mybb_sg_captcha` (
  `imagehash` varchar(32) NOT NULL DEFAULT '',
  `imagestring` varchar(8) NOT NULL DEFAULT '',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `used` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_datacache`
--

CREATE TABLE `mybb_sg_datacache` (
  `title` varchar(50) NOT NULL DEFAULT '',
  `cache` mediumtext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_delayedmoderation`
--

CREATE TABLE `mybb_sg_delayedmoderation` (
  `did` int(10) UNSIGNED NOT NULL,
  `type` varchar(30) NOT NULL DEFAULT '',
  `delaydateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `fid` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `tids` text NOT NULL,
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `inputs` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_events`
--

CREATE TABLE `mybb_sg_events` (
  `eid` int(10) UNSIGNED NOT NULL,
  `cid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(120) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '0',
  `private` tinyint(1) NOT NULL DEFAULT '0',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `starttime` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `endtime` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `timezone` varchar(5) NOT NULL DEFAULT '',
  `ignoretimezone` tinyint(1) NOT NULL DEFAULT '0',
  `usingtime` tinyint(1) NOT NULL DEFAULT '0',
  `repeats` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_forumpermissions`
--

CREATE TABLE `mybb_sg_forumpermissions` (
  `pid` int(10) UNSIGNED NOT NULL,
  `fid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `gid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `canview` tinyint(1) NOT NULL DEFAULT '0',
  `canviewthreads` tinyint(1) NOT NULL DEFAULT '0',
  `canonlyviewownthreads` tinyint(1) NOT NULL DEFAULT '0',
  `candlattachments` tinyint(1) NOT NULL DEFAULT '0',
  `canpostthreads` tinyint(1) NOT NULL DEFAULT '0',
  `canpostreplys` tinyint(1) NOT NULL DEFAULT '0',
  `canonlyreplyownthreads` tinyint(1) NOT NULL DEFAULT '0',
  `canpostattachments` tinyint(1) NOT NULL DEFAULT '0',
  `canratethreads` tinyint(1) NOT NULL DEFAULT '0',
  `caneditposts` tinyint(1) NOT NULL DEFAULT '0',
  `candeleteposts` tinyint(1) NOT NULL DEFAULT '0',
  `candeletethreads` tinyint(1) NOT NULL DEFAULT '0',
  `caneditattachments` tinyint(1) NOT NULL DEFAULT '0',
  `canviewdeletionnotice` tinyint(1) NOT NULL DEFAULT '0',
  `modposts` tinyint(1) NOT NULL DEFAULT '0',
  `modthreads` tinyint(1) NOT NULL DEFAULT '0',
  `mod_edit_posts` tinyint(1) NOT NULL DEFAULT '0',
  `modattachments` tinyint(1) NOT NULL DEFAULT '0',
  `canpostpolls` tinyint(1) NOT NULL DEFAULT '0',
  `canvotepolls` tinyint(1) NOT NULL DEFAULT '0',
  `cansearch` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_forums`
--

CREATE TABLE `mybb_sg_forums` (
  `fid` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `linkto` varchar(180) NOT NULL DEFAULT '',
  `type` char(1) NOT NULL DEFAULT '',
  `pid` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `parentlist` text NOT NULL,
  `disporder` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `open` tinyint(1) NOT NULL DEFAULT '0',
  `threads` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `posts` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `lastpost` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `lastposter` varchar(120) NOT NULL DEFAULT '',
  `lastposteruid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `lastposttid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `lastpostsubject` varchar(120) NOT NULL DEFAULT '',
  `allowhtml` tinyint(1) NOT NULL DEFAULT '0',
  `allowmycode` tinyint(1) NOT NULL DEFAULT '0',
  `allowsmilies` tinyint(1) NOT NULL DEFAULT '0',
  `allowimgcode` tinyint(1) NOT NULL DEFAULT '0',
  `allowvideocode` tinyint(1) NOT NULL DEFAULT '0',
  `allowpicons` tinyint(1) NOT NULL DEFAULT '0',
  `allowtratings` tinyint(1) NOT NULL DEFAULT '0',
  `usepostcounts` tinyint(1) NOT NULL DEFAULT '0',
  `usethreadcounts` tinyint(1) NOT NULL DEFAULT '0',
  `requireprefix` tinyint(1) NOT NULL DEFAULT '0',
  `password` varchar(50) NOT NULL DEFAULT '',
  `showinjump` tinyint(1) NOT NULL DEFAULT '0',
  `style` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `overridestyle` tinyint(1) NOT NULL DEFAULT '0',
  `rulestype` tinyint(1) NOT NULL DEFAULT '0',
  `rulestitle` varchar(200) NOT NULL DEFAULT '',
  `rules` text NOT NULL,
  `unapprovedthreads` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `unapprovedposts` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `deletedthreads` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `deletedposts` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `defaultdatecut` smallint(4) UNSIGNED NOT NULL DEFAULT '0',
  `defaultsortby` varchar(10) NOT NULL DEFAULT '',
  `defaultsortorder` varchar(4) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_forumsread`
--

CREATE TABLE `mybb_sg_forumsread` (
  `fid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_forumsubscriptions`
--

CREATE TABLE `mybb_sg_forumsubscriptions` (
  `fsid` int(10) UNSIGNED NOT NULL,
  `fid` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_groupleaders`
--

CREATE TABLE `mybb_sg_groupleaders` (
  `lid` smallint(5) UNSIGNED NOT NULL,
  `gid` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `canmanagemembers` tinyint(1) NOT NULL DEFAULT '0',
  `canmanagerequests` tinyint(1) NOT NULL DEFAULT '0',
  `caninvitemembers` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_hello_messages`
--

CREATE TABLE `mybb_sg_hello_messages` (
  `mid` int(10) UNSIGNED NOT NULL,
  `message` varchar(100) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_helpdocs`
--

CREATE TABLE `mybb_sg_helpdocs` (
  `hid` smallint(5) UNSIGNED NOT NULL,
  `sid` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(120) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `document` text NOT NULL,
  `usetranslation` tinyint(1) NOT NULL DEFAULT '0',
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `disporder` smallint(5) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_helpsections`
--

CREATE TABLE `mybb_sg_helpsections` (
  `sid` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `usetranslation` tinyint(1) NOT NULL DEFAULT '0',
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `disporder` smallint(5) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_icons`
--

CREATE TABLE `mybb_sg_icons` (
  `iid` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL DEFAULT '',
  `path` varchar(220) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_joinrequests`
--

CREATE TABLE `mybb_sg_joinrequests` (
  `rid` int(10) UNSIGNED NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `gid` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `reason` varchar(250) NOT NULL DEFAULT '',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `invite` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_mailerrors`
--

CREATE TABLE `mybb_sg_mailerrors` (
  `eid` int(10) UNSIGNED NOT NULL,
  `subject` varchar(200) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `toaddress` varchar(150) NOT NULL DEFAULT '',
  `fromaddress` varchar(150) NOT NULL DEFAULT '',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `error` text NOT NULL,
  `smtperror` varchar(200) NOT NULL DEFAULT '',
  `smtpcode` smallint(5) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_maillogs`
--

CREATE TABLE `mybb_sg_maillogs` (
  `mid` int(10) UNSIGNED NOT NULL,
  `subject` varchar(200) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `fromuid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `fromemail` varchar(200) NOT NULL DEFAULT '',
  `touid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `toemail` varchar(200) NOT NULL DEFAULT '',
  `tid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `ipaddress` varbinary(16) NOT NULL DEFAULT '',
  `type` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_mailqueue`
--

CREATE TABLE `mybb_sg_mailqueue` (
  `mid` int(10) UNSIGNED NOT NULL,
  `mailto` varchar(200) NOT NULL,
  `mailfrom` varchar(200) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `headers` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_massemails`
--

CREATE TABLE `mybb_sg_massemails` (
  `mid` int(10) UNSIGNED NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `subject` varchar(200) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `htmlmessage` text NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `format` tinyint(1) NOT NULL DEFAULT '0',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `senddate` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `sentcount` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `totalcount` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `conditions` text NOT NULL,
  `perpage` smallint(4) UNSIGNED NOT NULL DEFAULT '50'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_moderatorlog`
--

CREATE TABLE `mybb_sg_moderatorlog` (
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `fid` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `tid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `pid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `action` text NOT NULL,
  `data` text NOT NULL,
  `ipaddress` varbinary(16) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_moderators`
--

CREATE TABLE `mybb_sg_moderators` (
  `mid` smallint(5) UNSIGNED NOT NULL,
  `fid` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `isgroup` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `caneditposts` tinyint(1) NOT NULL DEFAULT '0',
  `cansoftdeleteposts` tinyint(1) NOT NULL DEFAULT '0',
  `canrestoreposts` tinyint(1) NOT NULL DEFAULT '0',
  `candeleteposts` tinyint(1) NOT NULL DEFAULT '0',
  `cansoftdeletethreads` tinyint(1) NOT NULL DEFAULT '0',
  `canrestorethreads` tinyint(1) NOT NULL DEFAULT '0',
  `candeletethreads` tinyint(1) NOT NULL DEFAULT '0',
  `canviewips` tinyint(1) NOT NULL DEFAULT '0',
  `canviewunapprove` tinyint(1) NOT NULL DEFAULT '0',
  `canviewdeleted` tinyint(1) NOT NULL DEFAULT '0',
  `canopenclosethreads` tinyint(1) NOT NULL DEFAULT '0',
  `canstickunstickthreads` tinyint(1) NOT NULL DEFAULT '0',
  `canapproveunapprovethreads` tinyint(1) NOT NULL DEFAULT '0',
  `canapproveunapproveposts` tinyint(1) NOT NULL DEFAULT '0',
  `canapproveunapproveattachs` tinyint(1) NOT NULL DEFAULT '0',
  `canmanagethreads` tinyint(1) NOT NULL DEFAULT '0',
  `canmanagepolls` tinyint(1) NOT NULL DEFAULT '0',
  `canpostclosedthreads` tinyint(1) NOT NULL DEFAULT '0',
  `canmovetononmodforum` tinyint(1) NOT NULL DEFAULT '0',
  `canusecustomtools` tinyint(1) NOT NULL DEFAULT '0',
  `canmanageannouncements` tinyint(1) NOT NULL DEFAULT '0',
  `canmanagereportedposts` tinyint(1) NOT NULL DEFAULT '0',
  `canviewmodlog` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_modtools`
--

CREATE TABLE `mybb_sg_modtools` (
  `tid` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `forums` text NOT NULL,
  `groups` text NOT NULL,
  `type` char(1) NOT NULL DEFAULT '',
  `postoptions` text NOT NULL,
  `threadoptions` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_mycode`
--

CREATE TABLE `mybb_sg_mycode` (
  `cid` int(10) UNSIGNED NOT NULL,
  `title` varchar(100) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `regex` text NOT NULL,
  `replacement` text NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `parseorder` smallint(5) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_newpoints_forumrules`
--

CREATE TABLE `mybb_sg_newpoints_forumrules` (
  `rid` bigint(30) UNSIGNED NOT NULL,
  `fid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `rate` float NOT NULL DEFAULT '1',
  `pointsview` decimal(16,2) NOT NULL DEFAULT '0.00',
  `pointspost` decimal(16,2) NOT NULL DEFAULT '0.00'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_newpoints_grouprules`
--

CREATE TABLE `mybb_sg_newpoints_grouprules` (
  `rid` bigint(30) UNSIGNED NOT NULL,
  `gid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `rate` float NOT NULL DEFAULT '1',
  `pointsearn` decimal(16,2) UNSIGNED NOT NULL DEFAULT '0.00',
  `period` bigint(30) UNSIGNED NOT NULL DEFAULT '0',
  `lastpay` bigint(30) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_newpoints_log`
--

CREATE TABLE `mybb_sg_newpoints_log` (
  `lid` bigint(30) UNSIGNED NOT NULL,
  `action` varchar(100) NOT NULL DEFAULT 'newpost',
  `data` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `uid` bigint(30) UNSIGNED NOT NULL DEFAULT '0',
  `username` varchar(100) NOT NULL DEFAULT '',
  `puntos_rol` float NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_newpoints_settings`
--

CREATE TABLE `mybb_sg_newpoints_settings` (
  `sid` int(10) UNSIGNED NOT NULL,
  `plugin` varchar(100) NOT NULL DEFAULT '',
  `name` varchar(100) NOT NULL DEFAULT '',
  `title` varchar(100) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `type` text NOT NULL,
  `value` text NOT NULL,
  `disporder` smallint(5) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_polls`
--

CREATE TABLE `mybb_sg_polls` (
  `pid` int(10) UNSIGNED NOT NULL,
  `tid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `question` varchar(200) NOT NULL DEFAULT '',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `options` text NOT NULL,
  `votes` text NOT NULL,
  `numoptions` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `numvotes` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `timeout` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `closed` tinyint(1) NOT NULL DEFAULT '0',
  `multiple` tinyint(1) NOT NULL DEFAULT '0',
  `public` tinyint(1) NOT NULL DEFAULT '0',
  `maxoptions` smallint(5) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_pollvotes`
--

CREATE TABLE `mybb_sg_pollvotes` (
  `vid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `voteoption` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `ipaddress` varbinary(16) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_posts`
--

CREATE TABLE `mybb_sg_posts` (
  `pid` int(10) UNSIGNED NOT NULL,
  `tid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `replyto` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `fid` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `subject` varchar(120) NOT NULL DEFAULT '',
  `icon` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `username` varchar(80) NOT NULL DEFAULT '',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `message` text NOT NULL,
  `ipaddress` varbinary(16) NOT NULL DEFAULT '',
  `includesig` tinyint(1) NOT NULL DEFAULT '0',
  `smilieoff` tinyint(1) NOT NULL DEFAULT '0',
  `edituid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `edittime` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `editreason` varchar(150) NOT NULL DEFAULT '',
  `visible` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_privatemessages`
--

CREATE TABLE `mybb_sg_privatemessages` (
  `pmid` int(10) UNSIGNED NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `toid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `fromid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `recipients` text NOT NULL,
  `folder` smallint(5) UNSIGNED NOT NULL DEFAULT '1',
  `subject` varchar(120) NOT NULL DEFAULT '',
  `icon` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `message` text NOT NULL,
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `deletetime` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `statustime` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `includesig` tinyint(1) NOT NULL DEFAULT '0',
  `smilieoff` tinyint(1) NOT NULL DEFAULT '0',
  `receipt` tinyint(1) NOT NULL DEFAULT '0',
  `readtime` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `ipaddress` varbinary(16) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_profilefields`
--

CREATE TABLE `mybb_sg_profilefields` (
  `fid` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `disporder` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `type` text NOT NULL,
  `regex` text NOT NULL,
  `length` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `maxlength` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `registration` tinyint(1) NOT NULL DEFAULT '0',
  `profile` tinyint(1) NOT NULL DEFAULT '0',
  `postbit` tinyint(1) NOT NULL DEFAULT '0',
  `viewableby` text NOT NULL,
  `editableby` text NOT NULL,
  `postnum` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `allowhtml` tinyint(1) NOT NULL DEFAULT '0',
  `allowmycode` tinyint(1) NOT NULL DEFAULT '0',
  `allowsmilies` tinyint(1) NOT NULL DEFAULT '0',
  `allowimgcode` tinyint(1) NOT NULL DEFAULT '0',
  `allowvideocode` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_promotionlogs`
--

CREATE TABLE `mybb_sg_promotionlogs` (
  `plid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `oldusergroup` varchar(200) NOT NULL DEFAULT '0',
  `newusergroup` smallint(6) NOT NULL DEFAULT '0',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `type` varchar(9) NOT NULL DEFAULT 'primary'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_promotions`
--

CREATE TABLE `mybb_sg_promotions` (
  `pid` int(10) UNSIGNED NOT NULL,
  `title` varchar(120) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `logging` tinyint(1) NOT NULL DEFAULT '0',
  `posts` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `posttype` char(2) NOT NULL DEFAULT '',
  `threads` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `threadtype` char(2) NOT NULL DEFAULT '',
  `registered` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `registeredtype` varchar(20) NOT NULL DEFAULT '',
  `online` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `onlinetype` varchar(20) NOT NULL DEFAULT '',
  `reputations` int(11) NOT NULL DEFAULT '0',
  `reputationtype` char(2) NOT NULL DEFAULT '',
  `referrals` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `referralstype` char(2) NOT NULL DEFAULT '',
  `warnings` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `warningstype` char(2) NOT NULL DEFAULT '',
  `requirements` varchar(200) NOT NULL DEFAULT '',
  `originalusergroup` varchar(120) NOT NULL DEFAULT '0',
  `newusergroup` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `usergrouptype` varchar(120) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_questions`
--

CREATE TABLE `mybb_sg_questions` (
  `qid` int(10) UNSIGNED NOT NULL,
  `question` varchar(200) NOT NULL DEFAULT '',
  `answer` varchar(150) NOT NULL DEFAULT '',
  `shown` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `correct` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `incorrect` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_questionsessions`
--

CREATE TABLE `mybb_sg_questionsessions` (
  `sid` varchar(32) NOT NULL DEFAULT '',
  `qid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_reportedcontent`
--

CREATE TABLE `mybb_sg_reportedcontent` (
  `rid` int(10) UNSIGNED NOT NULL,
  `id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id2` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id3` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `reportstatus` tinyint(1) NOT NULL DEFAULT '0',
  `reasonid` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `reason` varchar(250) NOT NULL DEFAULT '',
  `type` varchar(50) NOT NULL DEFAULT '',
  `reports` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `reporters` text NOT NULL,
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `lastreport` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_reportreasons`
--

CREATE TABLE `mybb_sg_reportreasons` (
  `rid` int(10) UNSIGNED NOT NULL,
  `title` varchar(250) NOT NULL DEFAULT '',
  `appliesto` varchar(250) NOT NULL DEFAULT '',
  `extra` tinyint(1) NOT NULL DEFAULT '0',
  `disporder` smallint(5) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_reputation`
--

CREATE TABLE `mybb_sg_reputation` (
  `rid` int(10) UNSIGNED NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `adduid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `pid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `reputation` smallint(6) NOT NULL DEFAULT '0',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `comments` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_searchlog`
--

CREATE TABLE `mybb_sg_searchlog` (
  `sid` varchar(32) NOT NULL DEFAULT '',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `ipaddress` varbinary(16) NOT NULL DEFAULT '',
  `threads` longtext NOT NULL,
  `posts` longtext NOT NULL,
  `resulttype` varchar(10) NOT NULL DEFAULT '',
  `querycache` text NOT NULL,
  `keywords` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sessions`
--

CREATE TABLE `mybb_sg_sessions` (
  `sid` varchar(32) NOT NULL DEFAULT '',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `ip` varbinary(16) NOT NULL DEFAULT '',
  `time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `location` varchar(150) NOT NULL DEFAULT '',
  `useragent` varchar(200) NOT NULL DEFAULT '',
  `anonymous` tinyint(1) NOT NULL DEFAULT '0',
  `nopermission` tinyint(1) NOT NULL DEFAULT '0',
  `location1` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `location2` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_settinggroups`
--

CREATE TABLE `mybb_sg_settinggroups` (
  `gid` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT '',
  `title` varchar(220) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `disporder` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `isdefault` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_settings`
--

CREATE TABLE `mybb_sg_settings` (
  `sid` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL DEFAULT '',
  `title` varchar(120) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `optionscode` text NOT NULL,
  `value` text NOT NULL,
  `disporder` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `gid` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `isdefault` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_audit_consola`
--

CREATE TABLE `mybb_sg_sg_audit_consola` (
  `id` int(11) NOT NULL,
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `staff` text COLLATE utf8_unicode_ci NOT NULL,
  `razon` text COLLATE utf8_unicode_ci NOT NULL,
  `log` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_audit_consola_mod`
--

CREATE TABLE `mybb_sg_sg_audit_consola_mod` (
  `id` int(11) NOT NULL,
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `staff` text COLLATE utf8_unicode_ci NOT NULL,
  `username` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `razon` text COLLATE utf8_unicode_ci NOT NULL,
  `log` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_audit_consola_tec`
--

CREATE TABLE `mybb_sg_sg_audit_consola_tec` (
  `id` int(11) NOT NULL,
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `staff` text COLLATE utf8_unicode_ci NOT NULL,
  `razon` text COLLATE utf8_unicode_ci NOT NULL,
  `log` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_audit_consola_tec_mod`
--

CREATE TABLE `mybb_sg_sg_audit_consola_tec_mod` (
  `id` int(11) NOT NULL,
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `staff` text COLLATE utf8_unicode_ci NOT NULL,
  `razon` text COLLATE utf8_unicode_ci NOT NULL,
  `log` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_audit_descripcion`
--

CREATE TABLE `mybb_sg_sg_audit_descripcion` (
  `fid` int(10) NOT NULL,
  `tiempo_editado` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `apodo` text COLLATE utf8_unicode_ci NOT NULL,
  `frase` text COLLATE utf8_unicode_ci NOT NULL,
  `extra` text COLLATE utf8_unicode_ci NOT NULL,
  `fisico_de_pj` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `personalidad` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `apariencia` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `historia` mediumtext COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_audit_entrenamientos`
--

CREATE TABLE `mybb_sg_sg_audit_entrenamientos` (
  `fid` int(10) NOT NULL,
  `nombre` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `tiempo_completado` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `puntos_habilidad` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pr` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `tiempo_iniciado` int(100) NOT NULL,
  `tiempo_finaliza` int(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_audit_general`
--

CREATE TABLE `mybb_sg_sg_audit_general` (
  `id` int(11) NOT NULL,
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `uid` text COLLATE utf8_unicode_ci NOT NULL,
  `username` text COLLATE utf8_unicode_ci NOT NULL,
  `user_uid` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '9999',
  `categoria` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `log` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_audit_misiones`
--

CREATE TABLE `mybb_sg_sg_audit_misiones` (
  `fid` int(10) NOT NULL,
  `nombre` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `tiempo_completado` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mid` int(11) NOT NULL,
  `ryos` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `puntos_habilidad` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pr` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `tiempo_iniciado` int(100) NOT NULL,
  `tiempo_finaliza` int(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_audit_recompensas`
--

CREATE TABLE `mybb_sg_sg_audit_recompensas` (
  `id` int(10) NOT NULL,
  `tiempo_completado` int(11) NOT NULL,
  `tiempo_nuevo` int(11) NOT NULL,
  `dia` int(11) NOT NULL,
  `season` int(11) NOT NULL DEFAULT 0, -- racha acumulada permanente (recompensa_diaria) - añadido 2026-06
  `uid` int(11) NOT NULL,
  `nombre` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `audit` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_audit_stats`
--

CREATE TABLE `mybb_sg_sg_audit_stats` (
  `fid` int(10) NOT NULL,
  `tiempo_editado` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `puntos_habilidad` int(11) NOT NULL,
  `str` int(3) NOT NULL,
  `res` int(3) NOT NULL,
  `spd` int(3) NOT NULL,
  `agi` int(3) NOT NULL,
  `dex` int(3) NOT NULL,
  `pres` int(3) NOT NULL,
  `inte` int(3) NOT NULL,
  `ctrl` int(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_clanes`
--

CREATE TABLE `mybb_sg_sg_clanes` (
  `cid` int(5) NOT NULL,
  `vid` int(5) NOT NULL,
  `nombreClan` varchar(20) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `totalUsuarios` int(2) DEFAULT NULL,
  `elementos` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `abierto` tinyint(1) NOT NULL DEFAULT '1',
  `descripcion` varchar(20000) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `img` varchar(1000) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `activo` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_codigos_admin`
--

CREATE TABLE `mybb_sg_sg_codigos_admin` (
  `id` int(100) NOT NULL,
  `codigo` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `expiracion_codigo` int(100) NOT NULL,
  `duracion` int(100) NOT NULL,
  `categoria` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `uso_unico` tinyint(1) NOT NULL DEFAULT '0',
  `usado` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_codigos_usuarios`
--

CREATE TABLE `mybb_sg_sg_codigos_usuarios` (
  `id` int(100) NOT NULL,
  `uid` int(3) NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `codigo` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `categoria` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `expiracion` int(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_dados`
--

CREATE TABLE `mybb_sg_sg_dados` (
  `did` int(10) UNSIGNED NOT NULL,
  `tid` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `dado_counter` int(11) NOT NULL,
  `dado_content` text CHARACTER SET utf8 NOT NULL,
  `tiempo_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_entrenamientos_usuarios`
--

CREATE TABLE `mybb_sg_sg_entrenamientos_usuarios` (
  `id` int(100) NOT NULL,
  `tid` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `uid` int(3) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tiempo_iniciado` int(100) NOT NULL,
  `tiempo_finaliza` int(100) NOT NULL,
  `duracion` int(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_espes`
--

CREATE TABLE `mybb_sg_sg_espes` (
  `eid` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `codigo` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `descripcion` mediumtext CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_experiencia_limite`
--

CREATE TABLE `mybb_sg_sg_experiencia_limite` (
  `id` int(11) NOT NULL,
  `uid` int(10) NOT NULL,
  `semana` int(10) NOT NULL,
  `experiencia_semanal` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_fichas`
--

CREATE TABLE `mybb_sg_sg_fichas` (
  `fid` int(10) NOT NULL,
  `puntos_habilidad` int(11) NOT NULL,
  `ryos` int(11) NOT NULL,
  `pe` int(11) NOT NULL DEFAULT '0',
  `reputacion` int(11) NOT NULL DEFAULT '0',
  `nombre` varchar(20) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `apodo` text CHARACTER SET utf8 COLLATE utf8_spanish_ci,
  `rango` text COLLATE utf8_unicode_ci NOT NULL,
  `limite_nivel` int(2) NOT NULL DEFAULT '10',
  `limite_clase` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'A',
  `edad` int(2) NOT NULL,
  `temporada_nacimiento` int(3) NOT NULL,
  `villa` varchar(20) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `clan` varchar(20) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `clan2` varchar(20) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `slots` int(3) NOT NULL DEFAULT '7',
  `espe` text COLLATE utf8_unicode_ci NOT NULL,
  `espe_estilo` text COLLATE utf8_unicode_ci NOT NULL,
  `maestria` text COLLATE utf8_unicode_ci NOT NULL,
  `maestria_secundaria` text COLLATE utf8_unicode_ci NOT NULL,
  `elemento1` varchar(15) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `elemento2` varchar(15) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `elemento3` varchar(15) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `elemento4` varchar(15) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `elemento5` varchar(15) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `renunciar_elemento` tinyint(1) NOT NULL DEFAULT '0',
  `apariencia` mediumtext CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `personalidad` mediumtext CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `historia` mediumtext CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `str` int(3) NOT NULL DEFAULT '5',
  `res` int(3) NOT NULL DEFAULT '5',
  `spd` int(3) NOT NULL DEFAULT '5',
  `agi` int(3) NOT NULL DEFAULT '5',
  `dex` int(3) NOT NULL DEFAULT '5',
  `pres` int(3) NOT NULL DEFAULT '5',
  `inte` int(3) NOT NULL DEFAULT '5',
  `ctrl` int(3) NOT NULL DEFAULT '5',
  `vida` int(11) NOT NULL DEFAULT '180',
  `chakra` int(11) NOT NULL DEFAULT '180',
  `regchakra` int(3) NOT NULL DEFAULT '3',
  `moderated` text COLLATE utf8_unicode_ci NOT NULL,
  `invocacion` text COLLATE utf8_unicode_ci NOT NULL,
  `invocacion_secundaria` text COLLATE utf8_unicode_ci NOT NULL,
  `pasiva_slot` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `kosei1` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `kosei2` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `notas` text COLLATE utf8_unicode_ci NOT NULL,
  `extra` text COLLATE utf8_unicode_ci NOT NULL,
  `frase` text COLLATE utf8_unicode_ci NOT NULL,
  `fisico_de_pj` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `banner` text COLLATE utf8_unicode_ci NOT NULL,
  `como_nos_conociste` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `virtudes` text COLLATE utf8_unicode_ci NOT NULL,
  `defectos` text COLLATE utf8_unicode_ci NOT NULL,
  `sexo` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `peso` int(11) NOT NULL DEFAULT '0',
  `altura` int(11) NOT NULL DEFAULT '0',
  `madara` int(11) NOT NULL DEFAULT '0',
  `tobi` int(11) NOT NULL DEFAULT '0',
  `rin` int(11) NOT NULL DEFAULT '0',
  `fuerza` int(11) NOT NULL DEFAULT '0',
  `destreza` int(11) NOT NULL DEFAULT '0',
  `cchakra` int(11) NOT NULL DEFAULT '0',
  `inteligencia` int(11) NOT NULL DEFAULT '0',
  `mfuerza` int(11) NOT NULL DEFAULT '1',
  `mdestreza` int(11) NOT NULL DEFAULT '1',
  `mcchakra` int(11) NOT NULL DEFAULT '1',
  `minteligencia` int(11) NOT NULL DEFAULT '1',
  `salud` int(11) NOT NULL DEFAULT '9',
  `velocidad` int(11) NOT NULL DEFAULT '9',
  `tenketsu` int(11) NOT NULL DEFAULT '9',
  `sigilo` int(11) NOT NULL DEFAULT '9',
  `pas_fuerza` int(11) NOT NULL DEFAULT '0',
  `pas_destreza` int(11) NOT NULL DEFAULT '0',
  `pas_cchakra` int(11) NOT NULL DEFAULT '0',
  `pas_inteligencia` int(11) NOT NULL DEFAULT '0',
  `pas_salud` int(11) NOT NULL DEFAULT '0',
  `pas_velocidad` int(11) NOT NULL DEFAULT '0',
  `pas_tenketsu` int(11) NOT NULL DEFAULT '0',
  `pas_sigilo` int(11) NOT NULL DEFAULT '0',
  `puede_usar_dojo` tinyint(1) NOT NULL DEFAULT '0',
  `puntos_estadistica` int(11) NOT NULL DEFAULT '15',
  `mejoras` int(11) NOT NULL DEFAULT '1',
  `nivel` int(11) NOT NULL DEFAULT '1',
  `tiempo_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Triggers `mybb_sg_sg_fichas`
--
DELIMITER $$
CREATE TRIGGER `log_fichas_b_u` BEFORE UPDATE ON `mybb_sg_sg_fichas` FOR EACH ROW insert into mybb_audit_prueba
values(old.fid,
      USER(),
       CURRENT_USER(),
      old.puntos_habilidad,
      new.puntos_habilidad,
      old.ryos,
      new.ryos,
      now())
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_hentai`
--

CREATE TABLE `mybb_sg_sg_hentai` (
  `uid` int(10) NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `enable_hentai` int(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_hide`
--

CREATE TABLE `mybb_sg_sg_hide` (
  `hid` int(10) UNSIGNED NOT NULL,
  `tid` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `hide_counter` int(11) NOT NULL,
  `show_hide` int(1) UNSIGNED NOT NULL DEFAULT '0',
  `hide_uids` varchar(255) NOT NULL DEFAULT '',
  `hide_content` text CHARACTER SET utf8 NOT NULL,
  `tiempo_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_intercambios`
--

CREATE TABLE `mybb_sg_sg_intercambios` (
  `id` int(10) NOT NULL,
  `uid` int(10) NOT NULL,
  `nombre` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `r_uid` int(10) NOT NULL,
  `r_nombre` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `tid` int(10) NOT NULL,
  `objetos` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `objetos_nombre` varchar(1000) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `dinero` int(100) NOT NULL,
  `razon` text COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` int(100) NOT NULL,
  `tiempo_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_inventario`
--

CREATE TABLE `mybb_sg_sg_inventario` (
  `id` int(3) NOT NULL,
  `objeto_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `uid` int(10) NOT NULL,
  `cantidad` int(10) NOT NULL,
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_likes`
--

CREATE TABLE `mybb_sg_sg_likes` (
  `pid` int(10) UNSIGNED NOT NULL,
  `tid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `fid` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `username` varchar(80) NOT NULL DEFAULT '',
  `subject` varchar(120) NOT NULL DEFAULT '',
  `liked_by_uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `liked_by_username` varchar(80) NOT NULL DEFAULT '',
  `liked_by_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_maestrias`
--

CREATE TABLE `mybb_sg_sg_maestrias` (
  `mid` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `codigo` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `codigo_tecnica` text COLLATE utf8_unicode_ci NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `descripcion` mediumtext CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_misiones_lista`
--

CREATE TABLE `mybb_sg_sg_misiones_lista` (
  `id` int(100) NOT NULL,
  `cod` int(100) NOT NULL COMMENT 'codigo único de la mision',
  `rango` text NOT NULL COMMENT 'Rango de mision',
  `niv` int(100) NOT NULL COMMENT 'nivel requerido para realizar la misión',
  `title` text NOT NULL COMMENT 'título de la misión',
  `descripcion` text NOT NULL COMMENT 'descripción de la misión',
  `ryos` int(100) NOT NULL COMMENT 'ryos obtenidos al completar la misión',
  `expt` int(100) NOT NULL COMMENT 'puntos de experiencia ganados al terminar la misión',
  `time` int(100) NOT NULL COMMENT 'tiempo requerido para completar la misión',
  `coste` int(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_misiones_usuarios`
--

CREATE TABLE `mybb_sg_sg_misiones_usuarios` (
  `id` int(100) NOT NULL,
  `cod` int(3) NOT NULL,
  `uid` int(3) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tiempo_iniciado` int(100) NOT NULL,
  `tiempo_finaliza` int(100) NOT NULL,
  `mision_duracion` int(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_npcs`
--

CREATE TABLE `mybb_sg_sg_npcs` (
  `npc_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `nombre` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `apodo` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `faccion` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `edad` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `temporada` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `rango` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'D',
  `fuerza` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `resistencia` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `velocidad` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `agilidad` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `destreza` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `presencia` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `inteligencia` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `vida` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `chakra` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `reg_chakra` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `apariencia` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `personalidad` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `historia1` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `historia2` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `historia3` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `extra` text COLLATE utf8_unicode_ci NOT NULL,
  `notas` text COLLATE utf8_unicode_ci NOT NULL,
  `avatar1` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'https://cdn.discordapp.com/attachments/835254788756602941/1203405082956267620/AvatarOculto_One_Piece_Gaiden_Foro_Rol.png',
  `avatar2` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'https://cdn.discordapp.com/attachments/835254788756602941/1203422974796103760/WantePerfilOculto_One_Piece_Gaiden_Foro_Rol.png',
  `tiempo_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_objetos`
--

CREATE TABLE `mybb_sg_sg_objetos` (
  `id` int(10) NOT NULL,
  `objeto_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `nombre` text NOT NULL,
  `tipo` text NOT NULL,
  `municion` varchar(255) NOT NULL DEFAULT '',
  `tamano` varchar(255) NOT NULL DEFAULT '',
  `descripcion` text NOT NULL,
  `coste` int(10) NOT NULL DEFAULT '99999',
  `inventario` int(4) NOT NULL DEFAULT '99',
  `imagen` text NOT NULL,
  `efecto` text NOT NULL,
  `en_tienda` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_pages`
--

CREATE TABLE `mybb_sg_sg_pages` (
  `id` int(11) NOT NULL,
  `queries` text NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  `username` varchar(80) NOT NULL DEFAULT '',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_peticiones`
--

CREATE TABLE `mybb_sg_sg_peticiones` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `resumen` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `url` varchar(255) NOT NULL,
  `resuelto` tinyint(1) NOT NULL DEFAULT '0',
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mod_uid` varchar(255) NOT NULL DEFAULT '',
  `mod_nombre` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_recompensas_usuarios`
--

CREATE TABLE `mybb_sg_sg_recompensas_usuarios` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `dia` int(11) NOT NULL,
  `season` int(11) NOT NULL DEFAULT 0, -- racha acumulada permanente (recompensa_diaria) - añadido 2026-06
  `tiempo` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_sabiasque`
--

CREATE TABLE `mybb_sg_sg_sabiasque` (
  `id` int(10) NOT NULL,
  `tipo` int(3) NOT NULL,
  `texto` varchar(1000) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_susurro`
--

CREATE TABLE `mybb_sg_sg_susurro` (
  `hid` int(10) UNSIGNED NOT NULL,
  `tid` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `hide_counter` int(11) NOT NULL,
  `susurro_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `hide_content` text CHARACTER SET utf8 NOT NULL,
  `tiempo_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_tecnicas`
--

CREATE TABLE `mybb_sg_sg_tecnicas` (
  `tid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `arbol` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `rama` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `tipo` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `aldea` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `categoria` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `sellos` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `rango` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `exclusiva` tinyint(1) NOT NULL DEFAULT '0',
  `acciones` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `coste` varchar(511) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `efecto` varchar(511) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `requisito` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `descripcion` mediumtext CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_tecnicas_version`
--

CREATE TABLE `mybb_sg_sg_tecnicas_version` (
  `tid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `tid_old` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `version` int(10) NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `tipo` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `aldea` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `categoria` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `sellos` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `rango` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `puntuacion` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `exclusiva` tinyint(1) NOT NULL DEFAULT '0',
  `coste` varchar(511) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `efecto` varchar(511) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `requisito` varchar(255) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `descripcion` mediumtext CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `balance` int(10) NOT NULL DEFAULT '0',
  `notas_balance` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `balance_prioridad` int(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_tec_aprendidas`
--

CREATE TABLE `mybb_sg_sg_tec_aprendidas` (
  `id` int(10) NOT NULL,
  `tid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `uid` int(3) NOT NULL,
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_tec_para_aprender`
--

CREATE TABLE `mybb_sg_sg_tec_para_aprender` (
  `id` int(10) NOT NULL,
  `tid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `uid` int(3) NOT NULL,
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_test`
--

CREATE TABLE `mybb_sg_sg_test` (
  `id` int(11) NOT NULL,
  `mytext` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_threads_cron`
--

CREATE TABLE `mybb_sg_sg_threads_cron` (
  `tid` int(10) NOT NULL,
  `year` int(10) NOT NULL,
  `month` int(10) NOT NULL,
  `day` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_thread_personaje`
--

CREATE TABLE `mybb_sg_sg_thread_personaje` (
  `id` int(10) NOT NULL,
  `tid` int(5) NOT NULL,
  `pid` int(5) NOT NULL,
  `uid` int(10) NOT NULL,
  `clase` varchar(10) NOT NULL,
  `fue` int(3) NOT NULL,
  `res` int(3) NOT NULL,
  `vel` int(3) NOT NULL,
  `agi` int(3) NOT NULL,
  `des` int(3) NOT NULL,
  `pre` int(3) NOT NULL,
  `int` int(3) NOT NULL,
  `cck` int(3) NOT NULL,
  `vida` int(3) NOT NULL,
  `chakra` int(3) NOT NULL,
  `regchakra` int(3) NOT NULL,
  `nombre` text NOT NULL,
  `espe` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `estilo` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `maestria` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `maestria2` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `inventario` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_tienda`
--

CREATE TABLE `mybb_sg_sg_tienda` (
  `eid` int(3) NOT NULL,
  `rango` int(2) NOT NULL,
  `nombreArma` varchar(30) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `tipo` varchar(50) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `categoria` text COLLATE utf8_unicode_ci NOT NULL,
  `descripcion` varchar(500) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `coste` int(4) NOT NULL,
  `cantidadMax` int(1) NOT NULL,
  `urlImagen` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_views`
--

CREATE TABLE `mybb_sg_sg_views` (
  `id` int(11) NOT NULL,
  `tid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `subject` varchar(120) NOT NULL DEFAULT '',
  `username` varchar(80) NOT NULL DEFAULT '',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_sg_villas`
--

CREATE TABLE `mybb_sg_sg_villas` (
  `vid` int(5) NOT NULL,
  `nombreVilla` varchar(30) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `abierta` tinyint(1) NOT NULL,
  `numUsers` int(3) NOT NULL,
  `img` varchar(1000) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `activa` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_smilies`
--

CREATE TABLE `mybb_sg_smilies` (
  `sid` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL DEFAULT '',
  `find` text NOT NULL,
  `image` varchar(220) NOT NULL DEFAULT '',
  `disporder` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `showclickable` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_spamlog`
--

CREATE TABLE `mybb_sg_spamlog` (
  `sid` int(10) UNSIGNED NOT NULL,
  `username` varchar(120) NOT NULL DEFAULT '',
  `email` varchar(220) NOT NULL DEFAULT '',
  `ipaddress` varbinary(16) NOT NULL DEFAULT '',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `data` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_spiders`
--

CREATE TABLE `mybb_sg_spiders` (
  `sid` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT '',
  `theme` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `language` varchar(20) NOT NULL DEFAULT '',
  `usergroup` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `useragent` varchar(200) NOT NULL DEFAULT '',
  `lastvisit` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_stats`
--

CREATE TABLE `mybb_sg_stats` (
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `numusers` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `numthreads` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `numposts` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_tasklog`
--

CREATE TABLE `mybb_sg_tasklog` (
  `lid` int(10) UNSIGNED NOT NULL,
  `tid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `data` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_tasks`
--

CREATE TABLE `mybb_sg_tasks` (
  `tid` int(10) UNSIGNED NOT NULL,
  `title` varchar(120) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `file` varchar(30) NOT NULL DEFAULT '',
  `minute` varchar(200) NOT NULL DEFAULT '',
  `hour` varchar(200) NOT NULL DEFAULT '',
  `day` varchar(100) NOT NULL DEFAULT '',
  `month` varchar(30) NOT NULL DEFAULT '',
  `weekday` varchar(15) NOT NULL DEFAULT '',
  `nextrun` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `lastrun` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `logging` tinyint(1) NOT NULL DEFAULT '0',
  `locked` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_templategroups`
--

CREATE TABLE `mybb_sg_templategroups` (
  `gid` int(10) UNSIGNED NOT NULL,
  `prefix` varchar(50) NOT NULL DEFAULT '',
  `title` varchar(100) NOT NULL DEFAULT '',
  `isdefault` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_templates`
--

CREATE TABLE `mybb_sg_templates` (
  `tid` int(10) UNSIGNED NOT NULL,
  `title` varchar(120) NOT NULL DEFAULT '',
  `template` text NOT NULL,
  `sid` smallint(6) NOT NULL DEFAULT '0',
  `version` varchar(20) NOT NULL DEFAULT '0',
  `status` varchar(10) NOT NULL DEFAULT '',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_templatesets`
--

CREATE TABLE `mybb_sg_templatesets` (
  `sid` smallint(5) UNSIGNED NOT NULL,
  `title` varchar(120) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_themes`
--

CREATE TABLE `mybb_sg_themes` (
  `tid` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT '',
  `pid` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `def` tinyint(1) NOT NULL DEFAULT '0',
  `properties` text NOT NULL,
  `stylesheets` text NOT NULL,
  `allowedgroups` text NOT NULL,
  `postlayout` varchar(15) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_themestylesheets`
--

CREATE TABLE `mybb_sg_themestylesheets` (
  `sid` int(10) UNSIGNED NOT NULL,
  `name` varchar(30) NOT NULL DEFAULT '',
  `tid` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `attachedto` text NOT NULL,
  `stylesheet` longtext NOT NULL,
  `cachefile` varchar(100) NOT NULL DEFAULT '',
  `lastmodified` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_threadprefixes`
--

CREATE TABLE `mybb_sg_threadprefixes` (
  `pid` int(10) UNSIGNED NOT NULL,
  `prefix` varchar(120) NOT NULL DEFAULT '',
  `displaystyle` varchar(200) NOT NULL DEFAULT '',
  `forums` text NOT NULL,
  `groups` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_threadratings`
--

CREATE TABLE `mybb_sg_threadratings` (
  `rid` int(10) UNSIGNED NOT NULL,
  `tid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `rating` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `ipaddress` varbinary(16) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_threads`
--

CREATE TABLE `mybb_sg_threads` (
  `tid` int(10) UNSIGNED NOT NULL,
  `fid` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `subject` varchar(120) NOT NULL DEFAULT '',
  `prefix` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `icon` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `poll` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `username` varchar(80) NOT NULL DEFAULT '',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `firstpost` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `lastpost` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `lastposter` varchar(120) NOT NULL DEFAULT '',
  `lastposteruid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `views` int(100) UNSIGNED NOT NULL DEFAULT '0',
  `replies` int(100) UNSIGNED NOT NULL DEFAULT '0',
  `closed` varchar(30) NOT NULL DEFAULT '',
  `sticky` tinyint(1) NOT NULL DEFAULT '0',
  `numratings` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `totalratings` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `notes` text NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '0',
  `unapprovedposts` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `deletedposts` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `attachmentcount` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `deletetime` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_threadsread`
--

CREATE TABLE `mybb_sg_threadsread` (
  `tid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_threadsubscriptions`
--

CREATE TABLE `mybb_sg_threadsubscriptions` (
  `sid` int(10) UNSIGNED NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `tid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `notification` tinyint(1) NOT NULL DEFAULT '0',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_threadviews`
--

CREATE TABLE `mybb_sg_threadviews` (
  `tid` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_userfields`
--

CREATE TABLE `mybb_sg_userfields` (
  `ufid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `fid1` text NOT NULL,
  `fid2` text NOT NULL,
  `fid3` text NOT NULL,
  `fid4` text,
  `fid5` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_usergroups`
--

CREATE TABLE `mybb_sg_usergroups` (
  `gid` smallint(5) UNSIGNED NOT NULL,
  `type` tinyint(1) UNSIGNED NOT NULL DEFAULT '2',
  `title` varchar(120) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `namestyle` varchar(200) NOT NULL DEFAULT '{username}',
  `usertitle` varchar(120) NOT NULL DEFAULT '',
  `stars` smallint(4) UNSIGNED NOT NULL DEFAULT '0',
  `starimage` varchar(120) NOT NULL DEFAULT '',
  `image` varchar(120) NOT NULL DEFAULT '',
  `disporder` smallint(6) UNSIGNED NOT NULL,
  `isbannedgroup` tinyint(1) NOT NULL DEFAULT '0',
  `canview` tinyint(1) NOT NULL DEFAULT '0',
  `canviewthreads` tinyint(1) NOT NULL DEFAULT '0',
  `canviewprofiles` tinyint(1) NOT NULL DEFAULT '0',
  `candlattachments` tinyint(1) NOT NULL DEFAULT '0',
  `canviewboardclosed` tinyint(1) NOT NULL DEFAULT '0',
  `canpostthreads` tinyint(1) NOT NULL DEFAULT '0',
  `canpostreplys` tinyint(1) NOT NULL DEFAULT '0',
  `canpostattachments` tinyint(1) NOT NULL DEFAULT '0',
  `canratethreads` tinyint(1) NOT NULL DEFAULT '0',
  `modposts` tinyint(1) NOT NULL DEFAULT '0',
  `modthreads` tinyint(1) NOT NULL DEFAULT '0',
  `mod_edit_posts` tinyint(1) NOT NULL DEFAULT '0',
  `modattachments` tinyint(1) NOT NULL DEFAULT '0',
  `caneditposts` tinyint(1) NOT NULL DEFAULT '0',
  `candeleteposts` tinyint(1) NOT NULL DEFAULT '0',
  `candeletethreads` tinyint(1) NOT NULL DEFAULT '0',
  `caneditattachments` tinyint(1) NOT NULL DEFAULT '0',
  `canviewdeletionnotice` tinyint(1) NOT NULL DEFAULT '0',
  `canpostpolls` tinyint(1) NOT NULL DEFAULT '0',
  `canvotepolls` tinyint(1) NOT NULL DEFAULT '0',
  `canundovotes` tinyint(1) NOT NULL DEFAULT '0',
  `canusepms` tinyint(1) NOT NULL DEFAULT '0',
  `cansendpms` tinyint(1) NOT NULL DEFAULT '0',
  `cantrackpms` tinyint(1) NOT NULL DEFAULT '0',
  `candenypmreceipts` tinyint(1) NOT NULL DEFAULT '0',
  `pmquota` int(3) UNSIGNED NOT NULL DEFAULT '0',
  `maxpmrecipients` int(4) UNSIGNED NOT NULL DEFAULT '5',
  `cansendemail` tinyint(1) NOT NULL DEFAULT '0',
  `cansendemailoverride` tinyint(1) NOT NULL DEFAULT '0',
  `maxemails` int(3) UNSIGNED NOT NULL DEFAULT '5',
  `emailfloodtime` int(3) UNSIGNED NOT NULL DEFAULT '5',
  `canviewmemberlist` tinyint(1) NOT NULL DEFAULT '0',
  `canviewcalendar` tinyint(1) NOT NULL DEFAULT '0',
  `canaddevents` tinyint(1) NOT NULL DEFAULT '0',
  `canbypasseventmod` tinyint(1) NOT NULL DEFAULT '0',
  `canmoderateevents` tinyint(1) NOT NULL DEFAULT '0',
  `canviewonline` tinyint(1) NOT NULL DEFAULT '0',
  `canviewwolinvis` tinyint(1) NOT NULL DEFAULT '0',
  `canviewonlineips` tinyint(1) NOT NULL DEFAULT '0',
  `cancp` tinyint(1) NOT NULL DEFAULT '0',
  `issupermod` tinyint(1) NOT NULL DEFAULT '0',
  `cansearch` tinyint(1) NOT NULL DEFAULT '0',
  `canusercp` tinyint(1) NOT NULL DEFAULT '0',
  `canuploadavatars` tinyint(1) NOT NULL DEFAULT '0',
  `canratemembers` tinyint(1) NOT NULL DEFAULT '0',
  `canchangename` tinyint(1) NOT NULL DEFAULT '0',
  `canbereported` tinyint(1) NOT NULL DEFAULT '0',
  `canbeinvisible` tinyint(1) NOT NULL DEFAULT '1',
  `canchangewebsite` tinyint(1) NOT NULL DEFAULT '1',
  `showforumteam` tinyint(1) NOT NULL DEFAULT '0',
  `usereputationsystem` tinyint(1) NOT NULL DEFAULT '0',
  `cangivereputations` tinyint(1) NOT NULL DEFAULT '0',
  `candeletereputations` tinyint(1) NOT NULL DEFAULT '0',
  `reputationpower` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `maxreputationsday` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `maxreputationsperuser` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `maxreputationsperthread` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `candisplaygroup` tinyint(1) NOT NULL DEFAULT '0',
  `attachquota` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `cancustomtitle` tinyint(1) NOT NULL DEFAULT '0',
  `canwarnusers` tinyint(1) NOT NULL DEFAULT '0',
  `canreceivewarnings` tinyint(1) NOT NULL DEFAULT '0',
  `maxwarningsday` int(3) UNSIGNED NOT NULL DEFAULT '3',
  `canmodcp` tinyint(1) NOT NULL DEFAULT '0',
  `showinbirthdaylist` tinyint(1) NOT NULL DEFAULT '0',
  `canoverridepm` tinyint(1) NOT NULL DEFAULT '0',
  `canusesig` tinyint(1) NOT NULL DEFAULT '0',
  `canusesigxposts` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `signofollow` tinyint(1) NOT NULL DEFAULT '0',
  `edittimelimit` int(4) UNSIGNED NOT NULL DEFAULT '0',
  `maxposts` int(4) UNSIGNED NOT NULL DEFAULT '0',
  `showmemberlist` tinyint(1) NOT NULL DEFAULT '1',
  `canmanageannounce` tinyint(1) NOT NULL DEFAULT '0',
  `canmanagemodqueue` tinyint(1) NOT NULL DEFAULT '0',
  `canmanagereportedcontent` tinyint(1) NOT NULL DEFAULT '0',
  `canviewmodlogs` tinyint(1) NOT NULL DEFAULT '0',
  `caneditprofiles` tinyint(1) NOT NULL DEFAULT '0',
  `canbanusers` tinyint(1) NOT NULL DEFAULT '0',
  `canviewwarnlogs` tinyint(1) NOT NULL DEFAULT '0',
  `canuseipsearch` tinyint(1) NOT NULL DEFAULT '0',
  `as_canswitch` int(1) NOT NULL DEFAULT '0',
  `as_limit` smallint(5) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_users`
--

CREATE TABLE `mybb_sg_users` (
  `uid` int(10) UNSIGNED NOT NULL,
  `username` varchar(120) NOT NULL DEFAULT '',
  `password` varchar(120) NOT NULL DEFAULT '',
  `salt` varchar(10) NOT NULL DEFAULT '',
  `loginkey` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(220) NOT NULL DEFAULT '',
  `postnum` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `threadnum` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `avatar` varchar(200) NOT NULL DEFAULT '',
  `avatar2` varchar(200) NOT NULL DEFAULT '/images/op/uploads/AvatarHabilidades_One_Piece_Gaiden_Foro_Rol.png',
  `avatardimensions` varchar(10) NOT NULL DEFAULT '',
  `avatartype` varchar(10) NOT NULL DEFAULT '0',
  `usergroup` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `additionalgroups` varchar(200) NOT NULL DEFAULT '',
  `displaygroup` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `usertitle` varchar(250) NOT NULL DEFAULT '',
  `regdate` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `lastactive` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `lastvisit` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `lastpost` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `website` varchar(200) NOT NULL DEFAULT '',
  `icq` varchar(10) NOT NULL DEFAULT '',
  `skype` varchar(75) NOT NULL DEFAULT '',
  `google` varchar(75) NOT NULL DEFAULT '',
  `birthday` varchar(15) NOT NULL DEFAULT '',
  `birthdayprivacy` varchar(4) NOT NULL DEFAULT 'all',
  `signature` text NOT NULL,
  `allownotices` tinyint(1) NOT NULL DEFAULT '0',
  `hideemail` tinyint(1) NOT NULL DEFAULT '0',
  `subscriptionmethod` tinyint(1) NOT NULL DEFAULT '0',
  `invisible` tinyint(1) NOT NULL DEFAULT '0',
  `receivepms` tinyint(1) NOT NULL DEFAULT '0',
  `receivefrombuddy` tinyint(1) NOT NULL DEFAULT '0',
  `pmnotice` tinyint(1) NOT NULL DEFAULT '0',
  `pmnotify` tinyint(1) NOT NULL DEFAULT '0',
  `buddyrequestspm` tinyint(1) NOT NULL DEFAULT '1',
  `buddyrequestsauto` tinyint(1) NOT NULL DEFAULT '0',
  `threadmode` varchar(8) NOT NULL DEFAULT '',
  `showimages` tinyint(1) NOT NULL DEFAULT '0',
  `showvideos` tinyint(1) NOT NULL DEFAULT '0',
  `showsigs` tinyint(1) NOT NULL DEFAULT '0',
  `showavatars` tinyint(1) NOT NULL DEFAULT '0',
  `showquickreply` tinyint(1) NOT NULL DEFAULT '0',
  `showredirect` tinyint(1) NOT NULL DEFAULT '0',
  `ppp` smallint(6) UNSIGNED NOT NULL DEFAULT '0',
  `tpp` smallint(6) UNSIGNED NOT NULL DEFAULT '0',
  `daysprune` smallint(6) UNSIGNED NOT NULL DEFAULT '0',
  `dateformat` varchar(4) NOT NULL DEFAULT '',
  `timeformat` varchar(4) NOT NULL DEFAULT '',
  `timezone` varchar(5) NOT NULL DEFAULT '',
  `dst` tinyint(1) NOT NULL DEFAULT '0',
  `dstcorrection` tinyint(1) NOT NULL DEFAULT '0',
  `buddylist` text NOT NULL,
  `ignorelist` text NOT NULL,
  `style` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `away` tinyint(1) NOT NULL DEFAULT '0',
  `awaydate` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `returndate` varchar(15) NOT NULL DEFAULT '',
  `awayreason` varchar(200) NOT NULL DEFAULT '',
  `pmfolders` text NOT NULL,
  `notepad` text NOT NULL,
  `referrer` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `referrals` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `reputation` int(11) NOT NULL DEFAULT '0',
  `regip` varbinary(16) NOT NULL DEFAULT '',
  `lastip` varbinary(16) NOT NULL DEFAULT '',
  `language` varchar(50) NOT NULL DEFAULT '',
  `timeonline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `showcodebuttons` tinyint(1) NOT NULL DEFAULT '1',
  `totalpms` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `unreadpms` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `warningpoints` int(3) UNSIGNED NOT NULL DEFAULT '0',
  `moderateposts` tinyint(1) NOT NULL DEFAULT '0',
  `moderationtime` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `suspendposting` tinyint(1) NOT NULL DEFAULT '0',
  `suspensiontime` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `suspendsignature` tinyint(1) NOT NULL DEFAULT '0',
  `suspendsigtime` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `coppauser` tinyint(1) NOT NULL DEFAULT '0',
  `classicpostbit` tinyint(1) NOT NULL DEFAULT '0',
  `loginattempts` smallint(2) UNSIGNED NOT NULL DEFAULT '0',
  `loginlockoutexpiry` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `usernotes` text NOT NULL,
  `sourceeditor` tinyint(1) NOT NULL DEFAULT '0',
  `newpoints` decimal(16,2) NOT NULL DEFAULT '0.00',
  `as_uid` int(11) NOT NULL DEFAULT '0',
  `as_share` int(1) NOT NULL DEFAULT '0',
  `as_shareuid` int(11) NOT NULL DEFAULT '0',
  `as_sec` int(1) NOT NULL DEFAULT '0',
  `as_secreason` varchar(500) NOT NULL DEFAULT '',
  `as_privacy` int(1) NOT NULL DEFAULT '0',
  `as_buddyshare` int(1) NOT NULL DEFAULT '0',
  `recentthread_show` int(11) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_usertitles`
--

CREATE TABLE `mybb_sg_usertitles` (
  `utid` smallint(5) UNSIGNED NOT NULL,
  `posts` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `title` varchar(250) NOT NULL DEFAULT '',
  `stars` smallint(4) UNSIGNED NOT NULL DEFAULT '0',
  `starimage` varchar(120) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_warninglevels`
--

CREATE TABLE `mybb_sg_warninglevels` (
  `lid` int(10) UNSIGNED NOT NULL,
  `percentage` smallint(3) UNSIGNED NOT NULL DEFAULT '0',
  `action` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_warnings`
--

CREATE TABLE `mybb_sg_warnings` (
  `wid` int(10) UNSIGNED NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `tid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `pid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `title` varchar(120) NOT NULL DEFAULT '',
  `points` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `dateline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `issuedby` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `expires` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `expired` tinyint(1) NOT NULL DEFAULT '0',
  `daterevoked` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `revokedby` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `revokereason` text NOT NULL,
  `notes` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mybb_sg_warningtypes`
--

CREATE TABLE `mybb_sg_warningtypes` (
  `tid` int(10) UNSIGNED NOT NULL,
  `title` varchar(120) NOT NULL DEFAULT '',
  `points` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `expirationtime` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mybb_sg_adminlog`
--
ALTER TABLE `mybb_sg_adminlog`
  ADD KEY `module` (`module`,`action`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `mybb_sg_adminoptions`
--
ALTER TABLE `mybb_sg_adminoptions`
  ADD PRIMARY KEY (`uid`);

--
-- Indexes for table `mybb_sg_adminviews`
--
ALTER TABLE `mybb_sg_adminviews`
  ADD PRIMARY KEY (`vid`);

--
-- Indexes for table `mybb_sg_announcements`
--
ALTER TABLE `mybb_sg_announcements`
  ADD PRIMARY KEY (`aid`),
  ADD KEY `fid` (`fid`);

--
-- Indexes for table `mybb_sg_attachments`
--
ALTER TABLE `mybb_sg_attachments`
  ADD PRIMARY KEY (`aid`),
  ADD KEY `pid` (`pid`,`visible`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `mybb_sg_attachtypes`
--
ALTER TABLE `mybb_sg_attachtypes`
  ADD PRIMARY KEY (`atid`);

--
-- Indexes for table `mybb_sg_awaitingactivation`
--
ALTER TABLE `mybb_sg_awaitingactivation`
  ADD PRIMARY KEY (`aid`);

--
-- Indexes for table `mybb_sg_badwords`
--
ALTER TABLE `mybb_sg_badwords`
  ADD PRIMARY KEY (`bid`);

--
-- Indexes for table `mybb_sg_banfilters`
--
ALTER TABLE `mybb_sg_banfilters`
  ADD PRIMARY KEY (`fid`),
  ADD KEY `type` (`type`);

--
-- Indexes for table `mybb_sg_banned`
--
ALTER TABLE `mybb_sg_banned`
  ADD KEY `uid` (`uid`),
  ADD KEY `dateline` (`dateline`);

--
-- Indexes for table `mybb_sg_buddyrequests`
--
ALTER TABLE `mybb_sg_buddyrequests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uid` (`uid`),
  ADD KEY `touid` (`touid`);

--
-- Indexes for table `mybb_sg_calendars`
--
ALTER TABLE `mybb_sg_calendars`
  ADD PRIMARY KEY (`cid`);

--
-- Indexes for table `mybb_sg_captcha`
--
ALTER TABLE `mybb_sg_captcha`
  ADD KEY `imagehash` (`imagehash`),
  ADD KEY `dateline` (`dateline`);

--
-- Indexes for table `mybb_sg_datacache`
--
ALTER TABLE `mybb_sg_datacache`
  ADD PRIMARY KEY (`title`);

--
-- Indexes for table `mybb_sg_delayedmoderation`
--
ALTER TABLE `mybb_sg_delayedmoderation`
  ADD PRIMARY KEY (`did`);

--
-- Indexes for table `mybb_sg_events`
--
ALTER TABLE `mybb_sg_events`
  ADD PRIMARY KEY (`eid`),
  ADD KEY `cid` (`cid`),
  ADD KEY `daterange` (`starttime`,`endtime`),
  ADD KEY `private` (`private`);

--
-- Indexes for table `mybb_sg_forumpermissions`
--
ALTER TABLE `mybb_sg_forumpermissions`
  ADD PRIMARY KEY (`pid`),
  ADD KEY `fid` (`fid`,`gid`);

--
-- Indexes for table `mybb_sg_forums`
--
ALTER TABLE `mybb_sg_forums`
  ADD PRIMARY KEY (`fid`);

--
-- Indexes for table `mybb_sg_forumsread`
--
ALTER TABLE `mybb_sg_forumsread`
  ADD UNIQUE KEY `fid` (`fid`,`uid`),
  ADD KEY `dateline` (`dateline`);

--
-- Indexes for table `mybb_sg_forumsubscriptions`
--
ALTER TABLE `mybb_sg_forumsubscriptions`
  ADD PRIMARY KEY (`fsid`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `mybb_sg_groupleaders`
--
ALTER TABLE `mybb_sg_groupleaders`
  ADD PRIMARY KEY (`lid`);

--
-- Indexes for table `mybb_sg_hello_messages`
--
ALTER TABLE `mybb_sg_hello_messages`
  ADD PRIMARY KEY (`mid`);

--
-- Indexes for table `mybb_sg_helpdocs`
--
ALTER TABLE `mybb_sg_helpdocs`
  ADD PRIMARY KEY (`hid`);

--
-- Indexes for table `mybb_sg_helpsections`
--
ALTER TABLE `mybb_sg_helpsections`
  ADD PRIMARY KEY (`sid`);

--
-- Indexes for table `mybb_sg_icons`
--
ALTER TABLE `mybb_sg_icons`
  ADD PRIMARY KEY (`iid`);

--
-- Indexes for table `mybb_sg_joinrequests`
--
ALTER TABLE `mybb_sg_joinrequests`
  ADD PRIMARY KEY (`rid`);

--
-- Indexes for table `mybb_sg_mailerrors`
--
ALTER TABLE `mybb_sg_mailerrors`
  ADD PRIMARY KEY (`eid`);

--
-- Indexes for table `mybb_sg_maillogs`
--
ALTER TABLE `mybb_sg_maillogs`
  ADD PRIMARY KEY (`mid`);

--
-- Indexes for table `mybb_sg_mailqueue`
--
ALTER TABLE `mybb_sg_mailqueue`
  ADD PRIMARY KEY (`mid`);

--
-- Indexes for table `mybb_sg_massemails`
--
ALTER TABLE `mybb_sg_massemails`
  ADD PRIMARY KEY (`mid`);

--
-- Indexes for table `mybb_sg_moderatorlog`
--
ALTER TABLE `mybb_sg_moderatorlog`
  ADD KEY `uid` (`uid`),
  ADD KEY `fid` (`fid`),
  ADD KEY `tid` (`tid`);

--
-- Indexes for table `mybb_sg_moderators`
--
ALTER TABLE `mybb_sg_moderators`
  ADD PRIMARY KEY (`mid`),
  ADD KEY `uid` (`id`,`fid`);

--
-- Indexes for table `mybb_sg_modtools`
--
ALTER TABLE `mybb_sg_modtools`
  ADD PRIMARY KEY (`tid`);

--
-- Indexes for table `mybb_sg_mycode`
--
ALTER TABLE `mybb_sg_mycode`
  ADD PRIMARY KEY (`cid`);

--
-- Indexes for table `mybb_sg_newpoints_forumrules`
--
ALTER TABLE `mybb_sg_newpoints_forumrules`
  ADD PRIMARY KEY (`rid`);

--
-- Indexes for table `mybb_sg_newpoints_grouprules`
--
ALTER TABLE `mybb_sg_newpoints_grouprules`
  ADD PRIMARY KEY (`rid`);

--
-- Indexes for table `mybb_sg_newpoints_log`
--
ALTER TABLE `mybb_sg_newpoints_log`
  ADD PRIMARY KEY (`lid`);

--
-- Indexes for table `mybb_sg_newpoints_settings`
--
ALTER TABLE `mybb_sg_newpoints_settings`
  ADD PRIMARY KEY (`sid`);

--
-- Indexes for table `mybb_sg_polls`
--
ALTER TABLE `mybb_sg_polls`
  ADD PRIMARY KEY (`pid`),
  ADD KEY `tid` (`tid`);

--
-- Indexes for table `mybb_sg_pollvotes`
--
ALTER TABLE `mybb_sg_pollvotes`
  ADD PRIMARY KEY (`vid`),
  ADD KEY `pid` (`pid`,`uid`);

--
-- Indexes for table `mybb_sg_posts`
--
ALTER TABLE `mybb_sg_posts`
  ADD PRIMARY KEY (`pid`),
  ADD KEY `tid` (`tid`,`uid`),
  ADD KEY `uid` (`uid`),
  ADD KEY `visible` (`visible`),
  ADD KEY `dateline` (`dateline`),
  ADD KEY `ipaddress` (`ipaddress`),
  ADD KEY `tiddate` (`tid`,`dateline`);
ALTER TABLE `mybb_sg_posts` ADD FULLTEXT KEY `message` (`message`);

--
-- Indexes for table `mybb_sg_privatemessages`
--
ALTER TABLE `mybb_sg_privatemessages`
  ADD PRIMARY KEY (`pmid`),
  ADD KEY `uid` (`uid`,`folder`),
  ADD KEY `toid` (`toid`);

--
-- Indexes for table `mybb_sg_profilefields`
--
ALTER TABLE `mybb_sg_profilefields`
  ADD PRIMARY KEY (`fid`);

--
-- Indexes for table `mybb_sg_promotionlogs`
--
ALTER TABLE `mybb_sg_promotionlogs`
  ADD PRIMARY KEY (`plid`);

--
-- Indexes for table `mybb_sg_promotions`
--
ALTER TABLE `mybb_sg_promotions`
  ADD PRIMARY KEY (`pid`);

--
-- Indexes for table `mybb_sg_questions`
--
ALTER TABLE `mybb_sg_questions`
  ADD PRIMARY KEY (`qid`);

--
-- Indexes for table `mybb_sg_questionsessions`
--
ALTER TABLE `mybb_sg_questionsessions`
  ADD PRIMARY KEY (`sid`);

--
-- Indexes for table `mybb_sg_reportedcontent`
--
ALTER TABLE `mybb_sg_reportedcontent`
  ADD PRIMARY KEY (`rid`),
  ADD KEY `reportstatus` (`reportstatus`),
  ADD KEY `lastreport` (`lastreport`);

--
-- Indexes for table `mybb_sg_reportreasons`
--
ALTER TABLE `mybb_sg_reportreasons`
  ADD PRIMARY KEY (`rid`);

--
-- Indexes for table `mybb_sg_reputation`
--
ALTER TABLE `mybb_sg_reputation`
  ADD PRIMARY KEY (`rid`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `mybb_sg_searchlog`
--
ALTER TABLE `mybb_sg_searchlog`
  ADD PRIMARY KEY (`sid`);

--
-- Indexes for table `mybb_sg_sessions`
--
ALTER TABLE `mybb_sg_sessions`
  ADD PRIMARY KEY (`sid`),
  ADD KEY `location` (`location1`,`location2`),
  ADD KEY `time` (`time`),
  ADD KEY `uid` (`uid`),
  ADD KEY `ip` (`ip`);

--
-- Indexes for table `mybb_sg_settinggroups`
--
ALTER TABLE `mybb_sg_settinggroups`
  ADD PRIMARY KEY (`gid`);

--
-- Indexes for table `mybb_sg_settings`
--
ALTER TABLE `mybb_sg_settings`
  ADD PRIMARY KEY (`sid`),
  ADD KEY `gid` (`gid`);

--
-- Indexes for table `mybb_sg_sg_audit_consola`
--
ALTER TABLE `mybb_sg_sg_audit_consola`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mybb_sg_sg_audit_consola_mod`
--
ALTER TABLE `mybb_sg_sg_audit_consola_mod`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mybb_sg_sg_audit_consola_tec`
--
ALTER TABLE `mybb_sg_sg_audit_consola_tec`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mybb_sg_sg_audit_consola_tec_mod`
--
ALTER TABLE `mybb_sg_sg_audit_consola_tec_mod`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mybb_sg_sg_audit_descripcion`
--
ALTER TABLE `mybb_sg_sg_audit_descripcion`
  ADD PRIMARY KEY (`fid`,`tiempo_editado`);

--
-- Indexes for table `mybb_sg_sg_audit_entrenamientos`
--
ALTER TABLE `mybb_sg_sg_audit_entrenamientos`
  ADD PRIMARY KEY (`fid`,`tiempo_completado`);

--
-- Indexes for table `mybb_sg_sg_audit_general`
--
ALTER TABLE `mybb_sg_sg_audit_general`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mybb_sg_sg_audit_misiones`
--
ALTER TABLE `mybb_sg_sg_audit_misiones`
  ADD PRIMARY KEY (`fid`,`tiempo_completado`);

--
-- Indexes for table `mybb_sg_sg_audit_recompensas`
--
ALTER TABLE `mybb_sg_sg_audit_recompensas`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `mybb_sg_sg_audit_stats`
--
ALTER TABLE `mybb_sg_sg_audit_stats`
  ADD PRIMARY KEY (`fid`,`tiempo_editado`);

--
-- Indexes for table `mybb_sg_sg_clanes`
--
ALTER TABLE `mybb_sg_sg_clanes`
  ADD PRIMARY KEY (`cid`),
  ADD KEY `vid_fk` (`vid`);

--
-- Indexes for table `mybb_sg_sg_codigos_admin`
--
ALTER TABLE `mybb_sg_sg_codigos_admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mybb_sg_sg_codigos_usuarios`
--
ALTER TABLE `mybb_sg_sg_codigos_usuarios`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mybb_sg_sg_dados`
--
ALTER TABLE `mybb_sg_sg_dados`
  ADD PRIMARY KEY (`did`);

--
-- Indexes for table `mybb_sg_sg_entrenamientos_usuarios`
--
ALTER TABLE `mybb_sg_sg_entrenamientos_usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uid` (`uid`);

--
-- Indexes for table `mybb_sg_sg_espes`
--
ALTER TABLE `mybb_sg_sg_espes`
  ADD PRIMARY KEY (`eid`);

--
-- Indexes for table `mybb_sg_sg_experiencia_limite`
--
ALTER TABLE `mybb_sg_sg_experiencia_limite`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mybb_sg_sg_fichas`
--
ALTER TABLE `mybb_sg_sg_fichas`
  ADD PRIMARY KEY (`fid`);

--
-- Indexes for table `mybb_sg_sg_hentai`
--
ALTER TABLE `mybb_sg_sg_hentai`
  ADD PRIMARY KEY (`uid`);

--
-- Indexes for table `mybb_sg_sg_hide`
--
ALTER TABLE `mybb_sg_sg_hide`
  ADD PRIMARY KEY (`hid`);

--
-- Indexes for table `mybb_sg_sg_inventario`
--
ALTER TABLE `mybb_sg_sg_inventario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `objeto_id` (`objeto_id`,`uid`);

--
-- Indexes for table `mybb_sg_sg_likes`
--
ALTER TABLE `mybb_sg_sg_likes`
  ADD PRIMARY KEY (`pid`,`liked_by_uid`);

--
-- Indexes for table `mybb_sg_sg_maestrias`
--
ALTER TABLE `mybb_sg_sg_maestrias`
  ADD PRIMARY KEY (`mid`);

--
-- Indexes for table `mybb_sg_sg_misiones_lista`
--
ALTER TABLE `mybb_sg_sg_misiones_lista`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mybb_sg_sg_misiones_usuarios`
--
ALTER TABLE `mybb_sg_sg_misiones_usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uid` (`uid`);

--
-- Indexes for table `mybb_sg_sg_objetos`
--
ALTER TABLE `mybb_sg_sg_objetos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `mybb_sg_sg_pages`
--
ALTER TABLE `mybb_sg_sg_pages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mybb_sg_sg_peticiones`
--
ALTER TABLE `mybb_sg_sg_peticiones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `mybb_sg_sg_recompensas_usuarios`
--
ALTER TABLE `mybb_sg_sg_recompensas_usuarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `mybb_sg_sg_sabiasque`
--
ALTER TABLE `mybb_sg_sg_sabiasque`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mybb_sg_sg_tecnicas`
--
ALTER TABLE `mybb_sg_sg_tecnicas`
  ADD PRIMARY KEY (`tid`);

--
-- Indexes for table `mybb_sg_sg_tecnicas_version`
--
ALTER TABLE `mybb_sg_sg_tecnicas_version`
  ADD PRIMARY KEY (`tid`,`version`);

--
-- Indexes for table `mybb_sg_sg_tec_aprendidas`
--
ALTER TABLE `mybb_sg_sg_tec_aprendidas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tid` (`tid`,`uid`);

--
-- Indexes for table `mybb_sg_sg_tec_para_aprender`
--
ALTER TABLE `mybb_sg_sg_tec_para_aprender`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tid` (`tid`,`uid`);

--
-- Indexes for table `mybb_sg_sg_test`
--
ALTER TABLE `mybb_sg_sg_test`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mybb_sg_sg_thread_personaje`
--
ALTER TABLE `mybb_sg_sg_thread_personaje`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tid` (`tid`,`uid`);

--
-- Indexes for table `mybb_sg_sg_tienda`
--
ALTER TABLE `mybb_sg_sg_tienda`
  ADD PRIMARY KEY (`eid`);

--
-- Indexes for table `mybb_sg_sg_views`
--
ALTER TABLE `mybb_sg_sg_views`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mybb_sg_sg_villas`
--
ALTER TABLE `mybb_sg_sg_villas`
  ADD PRIMARY KEY (`vid`);

--
-- Indexes for table `mybb_sg_smilies`
--
ALTER TABLE `mybb_sg_smilies`
  ADD PRIMARY KEY (`sid`);

--
-- Indexes for table `mybb_sg_spamlog`
--
ALTER TABLE `mybb_sg_spamlog`
  ADD PRIMARY KEY (`sid`);

--
-- Indexes for table `mybb_sg_spiders`
--
ALTER TABLE `mybb_sg_spiders`
  ADD PRIMARY KEY (`sid`);

--
-- Indexes for table `mybb_sg_stats`
--
ALTER TABLE `mybb_sg_stats`
  ADD PRIMARY KEY (`dateline`);

--
-- Indexes for table `mybb_sg_tasklog`
--
ALTER TABLE `mybb_sg_tasklog`
  ADD PRIMARY KEY (`lid`);

--
-- Indexes for table `mybb_sg_tasks`
--
ALTER TABLE `mybb_sg_tasks`
  ADD PRIMARY KEY (`tid`);

--
-- Indexes for table `mybb_sg_templategroups`
--
ALTER TABLE `mybb_sg_templategroups`
  ADD PRIMARY KEY (`gid`);

--
-- Indexes for table `mybb_sg_templates`
--
ALTER TABLE `mybb_sg_templates`
  ADD PRIMARY KEY (`tid`),
  ADD KEY `sid` (`sid`,`title`);

--
-- Indexes for table `mybb_sg_templatesets`
--
ALTER TABLE `mybb_sg_templatesets`
  ADD PRIMARY KEY (`sid`);

--
-- Indexes for table `mybb_sg_themes`
--
ALTER TABLE `mybb_sg_themes`
  ADD PRIMARY KEY (`tid`);

--
-- Indexes for table `mybb_sg_themestylesheets`
--
ALTER TABLE `mybb_sg_themestylesheets`
  ADD PRIMARY KEY (`sid`),
  ADD KEY `tid` (`tid`);

--
-- Indexes for table `mybb_sg_threadprefixes`
--
ALTER TABLE `mybb_sg_threadprefixes`
  ADD PRIMARY KEY (`pid`);

--
-- Indexes for table `mybb_sg_threadratings`
--
ALTER TABLE `mybb_sg_threadratings`
  ADD PRIMARY KEY (`rid`),
  ADD KEY `tid` (`tid`,`uid`);

--
-- Indexes for table `mybb_sg_threads`
--
ALTER TABLE `mybb_sg_threads`
  ADD PRIMARY KEY (`tid`),
  ADD KEY `fid` (`fid`,`visible`,`sticky`),
  ADD KEY `dateline` (`dateline`),
  ADD KEY `lastpost` (`lastpost`,`fid`),
  ADD KEY `firstpost` (`firstpost`),
  ADD KEY `uid` (`uid`);
ALTER TABLE `mybb_sg_threads` ADD FULLTEXT KEY `subject` (`subject`);

--
-- Indexes for table `mybb_sg_threadsread`
--
ALTER TABLE `mybb_sg_threadsread`
  ADD UNIQUE KEY `tid` (`tid`,`uid`),
  ADD KEY `dateline` (`dateline`);

--
-- Indexes for table `mybb_sg_threadsubscriptions`
--
ALTER TABLE `mybb_sg_threadsubscriptions`
  ADD PRIMARY KEY (`sid`),
  ADD KEY `uid` (`uid`),
  ADD KEY `tid` (`tid`,`notification`);

--
-- Indexes for table `mybb_sg_threadviews`
--
ALTER TABLE `mybb_sg_threadviews`
  ADD KEY `tid` (`tid`);

--
-- Indexes for table `mybb_sg_userfields`
--
ALTER TABLE `mybb_sg_userfields`
  ADD PRIMARY KEY (`ufid`);

--
-- Indexes for table `mybb_sg_usergroups`
--
ALTER TABLE `mybb_sg_usergroups`
  ADD PRIMARY KEY (`gid`);

--
-- Indexes for table `mybb_sg_users`
--
ALTER TABLE `mybb_sg_users`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `usergroup` (`usergroup`),
  ADD KEY `regip` (`regip`),
  ADD KEY `lastip` (`lastip`);

--
-- Indexes for table `mybb_sg_usertitles`
--
ALTER TABLE `mybb_sg_usertitles`
  ADD PRIMARY KEY (`utid`);

--
-- Indexes for table `mybb_sg_warninglevels`
--
ALTER TABLE `mybb_sg_warninglevels`
  ADD PRIMARY KEY (`lid`);

--
-- Indexes for table `mybb_sg_warnings`
--
ALTER TABLE `mybb_sg_warnings`
  ADD PRIMARY KEY (`wid`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `mybb_sg_warningtypes`
--
ALTER TABLE `mybb_sg_warningtypes`
  ADD PRIMARY KEY (`tid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mybb_sg_adminviews`
--
ALTER TABLE `mybb_sg_adminviews`
  MODIFY `vid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_announcements`
--
ALTER TABLE `mybb_sg_announcements`
  MODIFY `aid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_attachments`
--
ALTER TABLE `mybb_sg_attachments`
  MODIFY `aid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_attachtypes`
--
ALTER TABLE `mybb_sg_attachtypes`
  MODIFY `atid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_awaitingactivation`
--
ALTER TABLE `mybb_sg_awaitingactivation`
  MODIFY `aid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_badwords`
--
ALTER TABLE `mybb_sg_badwords`
  MODIFY `bid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_banfilters`
--
ALTER TABLE `mybb_sg_banfilters`
  MODIFY `fid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_buddyrequests`
--
ALTER TABLE `mybb_sg_buddyrequests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_calendars`
--
ALTER TABLE `mybb_sg_calendars`
  MODIFY `cid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_delayedmoderation`
--
ALTER TABLE `mybb_sg_delayedmoderation`
  MODIFY `did` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_events`
--
ALTER TABLE `mybb_sg_events`
  MODIFY `eid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_forumpermissions`
--
ALTER TABLE `mybb_sg_forumpermissions`
  MODIFY `pid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_forums`
--
ALTER TABLE `mybb_sg_forums`
  MODIFY `fid` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_forumsubscriptions`
--
ALTER TABLE `mybb_sg_forumsubscriptions`
  MODIFY `fsid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_groupleaders`
--
ALTER TABLE `mybb_sg_groupleaders`
  MODIFY `lid` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_hello_messages`
--
ALTER TABLE `mybb_sg_hello_messages`
  MODIFY `mid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_helpdocs`
--
ALTER TABLE `mybb_sg_helpdocs`
  MODIFY `hid` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_helpsections`
--
ALTER TABLE `mybb_sg_helpsections`
  MODIFY `sid` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_icons`
--
ALTER TABLE `mybb_sg_icons`
  MODIFY `iid` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_joinrequests`
--
ALTER TABLE `mybb_sg_joinrequests`
  MODIFY `rid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_mailerrors`
--
ALTER TABLE `mybb_sg_mailerrors`
  MODIFY `eid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_maillogs`
--
ALTER TABLE `mybb_sg_maillogs`
  MODIFY `mid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_mailqueue`
--
ALTER TABLE `mybb_sg_mailqueue`
  MODIFY `mid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_massemails`
--
ALTER TABLE `mybb_sg_massemails`
  MODIFY `mid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_moderators`
--
ALTER TABLE `mybb_sg_moderators`
  MODIFY `mid` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_modtools`
--
ALTER TABLE `mybb_sg_modtools`
  MODIFY `tid` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_mycode`
--
ALTER TABLE `mybb_sg_mycode`
  MODIFY `cid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_newpoints_forumrules`
--
ALTER TABLE `mybb_sg_newpoints_forumrules`
  MODIFY `rid` bigint(30) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_newpoints_grouprules`
--
ALTER TABLE `mybb_sg_newpoints_grouprules`
  MODIFY `rid` bigint(30) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_newpoints_log`
--
ALTER TABLE `mybb_sg_newpoints_log`
  MODIFY `lid` bigint(30) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_newpoints_settings`
--
ALTER TABLE `mybb_sg_newpoints_settings`
  MODIFY `sid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_polls`
--
ALTER TABLE `mybb_sg_polls`
  MODIFY `pid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_pollvotes`
--
ALTER TABLE `mybb_sg_pollvotes`
  MODIFY `vid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_posts`
--
ALTER TABLE `mybb_sg_posts`
  MODIFY `pid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_privatemessages`
--
ALTER TABLE `mybb_sg_privatemessages`
  MODIFY `pmid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_profilefields`
--
ALTER TABLE `mybb_sg_profilefields`
  MODIFY `fid` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_promotionlogs`
--
ALTER TABLE `mybb_sg_promotionlogs`
  MODIFY `plid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_promotions`
--
ALTER TABLE `mybb_sg_promotions`
  MODIFY `pid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_questions`
--
ALTER TABLE `mybb_sg_questions`
  MODIFY `qid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_reportedcontent`
--
ALTER TABLE `mybb_sg_reportedcontent`
  MODIFY `rid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_reportreasons`
--
ALTER TABLE `mybb_sg_reportreasons`
  MODIFY `rid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_reputation`
--
ALTER TABLE `mybb_sg_reputation`
  MODIFY `rid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_settinggroups`
--
ALTER TABLE `mybb_sg_settinggroups`
  MODIFY `gid` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_settings`
--
ALTER TABLE `mybb_sg_settings`
  MODIFY `sid` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_audit_consola`
--
ALTER TABLE `mybb_sg_sg_audit_consola`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_audit_consola_mod`
--
ALTER TABLE `mybb_sg_sg_audit_consola_mod`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_audit_consola_tec`
--
ALTER TABLE `mybb_sg_sg_audit_consola_tec`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_audit_consola_tec_mod`
--
ALTER TABLE `mybb_sg_sg_audit_consola_tec_mod`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_audit_general`
--
ALTER TABLE `mybb_sg_sg_audit_general`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_audit_recompensas`
--
ALTER TABLE `mybb_sg_sg_audit_recompensas`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_clanes`
--
ALTER TABLE `mybb_sg_sg_clanes`
  MODIFY `cid` int(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_codigos_admin`
--
ALTER TABLE `mybb_sg_sg_codigos_admin`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_codigos_usuarios`
--
ALTER TABLE `mybb_sg_sg_codigos_usuarios`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_dados`
--
ALTER TABLE `mybb_sg_sg_dados`
  MODIFY `did` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_entrenamientos_usuarios`
--
ALTER TABLE `mybb_sg_sg_entrenamientos_usuarios`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_experiencia_limite`
--
ALTER TABLE `mybb_sg_sg_experiencia_limite`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_hide`
--
ALTER TABLE `mybb_sg_sg_hide`
  MODIFY `hid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_inventario`
--
ALTER TABLE `mybb_sg_sg_inventario`
  MODIFY `id` int(3) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_misiones_lista`
--
ALTER TABLE `mybb_sg_sg_misiones_lista`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_misiones_usuarios`
--
ALTER TABLE `mybb_sg_sg_misiones_usuarios`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_objetos`
--
ALTER TABLE `mybb_sg_sg_objetos`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_pages`
--
ALTER TABLE `mybb_sg_sg_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_peticiones`
--
ALTER TABLE `mybb_sg_sg_peticiones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_recompensas_usuarios`
--
ALTER TABLE `mybb_sg_sg_recompensas_usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_sabiasque`
--
ALTER TABLE `mybb_sg_sg_sabiasque`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_tec_aprendidas`
--
ALTER TABLE `mybb_sg_sg_tec_aprendidas`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_tec_para_aprender`
--
ALTER TABLE `mybb_sg_sg_tec_para_aprender`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_test`
--
ALTER TABLE `mybb_sg_sg_test`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_thread_personaje`
--
ALTER TABLE `mybb_sg_sg_thread_personaje`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_tienda`
--
ALTER TABLE `mybb_sg_sg_tienda`
  MODIFY `eid` int(3) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_views`
--
ALTER TABLE `mybb_sg_sg_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_sg_villas`
--
ALTER TABLE `mybb_sg_sg_villas`
  MODIFY `vid` int(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_smilies`
--
ALTER TABLE `mybb_sg_smilies`
  MODIFY `sid` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_spamlog`
--
ALTER TABLE `mybb_sg_spamlog`
  MODIFY `sid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_spiders`
--
ALTER TABLE `mybb_sg_spiders`
  MODIFY `sid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_tasklog`
--
ALTER TABLE `mybb_sg_tasklog`
  MODIFY `lid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_tasks`
--
ALTER TABLE `mybb_sg_tasks`
  MODIFY `tid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_templategroups`
--
ALTER TABLE `mybb_sg_templategroups`
  MODIFY `gid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_templates`
--
ALTER TABLE `mybb_sg_templates`
  MODIFY `tid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_templatesets`
--
ALTER TABLE `mybb_sg_templatesets`
  MODIFY `sid` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_themes`
--
ALTER TABLE `mybb_sg_themes`
  MODIFY `tid` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_themestylesheets`
--
ALTER TABLE `mybb_sg_themestylesheets`
  MODIFY `sid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_threadprefixes`
--
ALTER TABLE `mybb_sg_threadprefixes`
  MODIFY `pid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_threadratings`
--
ALTER TABLE `mybb_sg_threadratings`
  MODIFY `rid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_threads`
--
ALTER TABLE `mybb_sg_threads`
  MODIFY `tid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_threadsubscriptions`
--
ALTER TABLE `mybb_sg_threadsubscriptions`
  MODIFY `sid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_usergroups`
--
ALTER TABLE `mybb_sg_usergroups`
  MODIFY `gid` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_users`
--
ALTER TABLE `mybb_sg_users`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_usertitles`
--
ALTER TABLE `mybb_sg_usertitles`
  MODIFY `utid` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_warninglevels`
--
ALTER TABLE `mybb_sg_warninglevels`
  MODIFY `lid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_warnings`
--
ALTER TABLE `mybb_sg_warnings`
  MODIFY `wid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mybb_sg_warningtypes`
--
ALTER TABLE `mybb_sg_warningtypes`
  MODIFY `tid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `mybb_sg_sg_clanes`
--
ALTER TABLE `mybb_sg_sg_clanes`
  ADD CONSTRAINT `vid_fk` FOREIGN KEY (`vid`) REFERENCES `mybb_sg_sg_villas` (`vid`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

CREATE TABLE `User` (
  `u_id` varchar(255) PRIMARY KEY,
  `u_accessLevel` ENUM ('User', 'Admin'),
  `u_fName` varchar(64),
  `u_lName` varchar(64),
  `u_email` varchar(128),
  `u_phone` varchar(32),
  `u_dateCreated` timestamp,
  `u_dateUpdated` timestamp
);

CREATE TABLE `UserAuth` (
  `ua_id` int(11) PRIMARY KEY,
  `ua_u_id` varchar(16),
  `ua_uap_id` int(11),
  `ua_providerId` varchar(128)
);

CREATE TABLE `UserAuthProvider` (
  `uap_id` int(11) PRIMARY KEY,
  `uap_name` varchar(32) COMMENT 'eg Google, ONID, Microsoft Exchange',
  `uap_active` boolean COMMENT 'Whether a user can still use this method'
);

CREATE TABLE `LocalAuth` (
  `la_id` varchar(16) PRIMARY KEY,
  `la_password` varchar(512) COMMENT 'Should ALWAYS be hashed',
  `la_resetCode` varchar(128),
  `la_resetExpires` timestamp
);

CREATE TABLE `Position` (
  `p_id` varchar(16) PRIMARY KEY,
  `p_title` varchar(128),
  `p_postingLink` varchar(128) COMMENT 'The URL for the job posting',
  `p_dateCreated` timestamp,
  `p_committeeEmail` varchar(128) COMMENT 'The email address to send updates from & cc (so all committee members can see it & are in reply chain)',
  `p_status` ENUM ('Requested', 'Open', 'Interviewing', 'Closed')
);

CREATE TABLE `RoleForPosition` (
  `rfp_id` int(11) PRIMARY KEY,
  `rfp_u_id` varchar(16),
  `rfp_p_id` varchar(16),
  `rfp_r_id` int(11)
);

CREATE TABLE `Role` (
  `pre_id` int(11) PRIMARY KEY,
  `pre_name` varchar(32) COMMENT '"Hiring Manager"
"Search Chair"
"Search Administrator"
"Search Advocate"
"Member"
"Human Resources"
"Other"'
);

CREATE TABLE `Candidate` (
  `c_id` varchar(16) PRIMARY KEY,
  `c_fName` varchar(64),
  `c_lName` varchar(64),
  `c_location` varchar(64),
  `c_email` varchar(64),
  `c_phone` varchar(16),
  `c_cs_id` int(11),
  `c_p_id` varchar(16) COMMENT 'The position the candidate applied for',
  `c_dateCreated` timestamp
);

CREATE TABLE `CandidateStatus` (
  `cs_id` int(11) PRIMARY KEY,
  `cs_cse_id` int(11) COMMENT 'Final decision for candidate',
  `cs_specificDispositionReason` varchar(255) COMMENT 'The reason for the decision',
  `cs_u_id` varchar(16) COMMENT 'The responsible party',
  `cs_responsiblePartyDescription` varchar(255) COMMENT 'Used if the responsible party is \'other\'',
  `cs_comments` varchar(255),
  `cs_howNotified` varchar(32) COMMENT 'How the candidate was told their decision',
  `cs_dateDecided` timestamp
);

CREATE TABLE `CandidateStatusEnum` (
  `cse_id` int(11) PRIMARY KEY,
  `cse_name` varchar(64) COMMENT 'Description of Status type (eg Rejected)',
  `cse_active` boolean COMMENT 'Whether a new position can recieve this Status'
);

CREATE TABLE `CandidateFiles` (
  `cf_id` int(11) PRIMARY KEY,
  `cf_c_id` varchar(16),
  `cf_fileName` varchar(255),
  `cf_purpose` varchar(64) COMMENT 'eg \'Resume\'',
  `cf_dateCreated` timestamp
);

CREATE TABLE `CandidateRoundNote` (
  `crn_id` int(11) PRIMARY KEY,
  `crn_c_id` varchar(16),
  `crn_r_id` varchar(16),
  `crn_notes` text,
  `crn_dateUpdated` timestamp
);

CREATE TABLE `Qualification` (
  `q_id` varchar(16) PRIMARY KEY,
  `q_p_id` varchar(16),
  `q_level` ENUM ('Minimum', 'Preferred'),
  `q_description` text COMMENT 'Relationship to job in spreadsheet',
  `q_transferable` boolean,
  `q_screeningCriteria` varchar(256) COMMENT 'Screening Criteria in spreadsheet',
  `q_priority` ENUM ('High', 'Medium', 'Low') COMMENT 'Priority in spreadsheet',
  `q_strengthIndicators` text COMMENT 'Strength in spreadsheet',
  `q_dateCreated` timestamp
);

CREATE TABLE `Round` (
  `r_id` varchar(16) PRIMARY KEY,
  `r_p_id` varchar(16),
  `r_name` varchar(255),
  `r_interviewQLink` varchar(512) COMMENT 'Link to uploaded interview questions',
  `r_dateCreated` timestamp
);

CREATE TABLE `Feedback` (
  `f_id` int(11) PRIMARY KEY,
  `f_u_id` varchar(16),
  `f_c_id` varchar(16),
  `f_r_id` varchar(16),
  `f_notes` text COMMENT 'The user\'s notes about the candidate during this round',
  `f_lastUpdated` timestamp
);

CREATE TABLE `FeedbackFiles` (
  `ff_id` int(11) PRIMARY KEY,
  `ff_f_id` int(11),
  `ff_fileName` varchar(64),
  `f_dateCreated` timestamp
);

CREATE TABLE `QualificationStatusEnum` (
  `qse_id` int(11) PRIMARY KEY,
  `qse_name` varchar(16) COMMENT 'eg Exceeds, Meets, Does Not Meet.'
);

CREATE TABLE `QualificationForRound` (
  `qfr_r_id` varchar(16),
  `qfr_q_id` varchar(16)
);

CREATE TABLE `FeedbackForQual` (
  `ffq_f_id` int(11),
  `ffq_q_id` int(11),
  `ffq_fqe_id` int(11),
  `ffq_dateSaved` timestamp
);

CREATE TABLE `Message` (
  `m_id` int(11),
  `m_subject` varchar(256),
  `m_body` text,
  `m_purpose` varchar(256),
  `m_inserts` varchar(256)
);

ALTER TABLE `User` COMMENT = 'Users are people on a search committee';

ALTER TABLE `Position` COMMENT = 'Holds information about positions that are hiring';

ALTER TABLE `RoleForPosition` COMMENT = 'Defines a user\'s permission level for a given position (comittee)';

ALTER TABLE `Role` COMMENT = 'Kept as a table per Don\'s request';

ALTER TABLE `Candidate` COMMENT = 'Holds information about a candidate for a given position. (Duplicates created for candidates that apply to several positions).';

ALTER TABLE `CandidateStatus` COMMENT = 'Holds information about the final decision for a candidate';

ALTER TABLE `CandidateFiles` COMMENT = 'Holds uploads for a candidate, such as their resume';

ALTER TABLE `Qualification` COMMENT = 'Holds info about each qualification for a given position';

ALTER TABLE `Round` COMMENT = 'Holds information for a single round (eg resume review, phone interview, etc)';

ALTER TABLE `Feedback` COMMENT = 'Holds information about a single user\'s feedback for a given round';

ALTER TABLE `FeedbackFiles` COMMENT = 'Gives users the option to upload files & pictures instead of typing notes';

ALTER TABLE `QualificationStatusEnum` COMMENT = 'Leaving in a table per Don\'s request (-- potential future expansion to let each position have different options)';

ALTER TABLE `FeedbackForQual` COMMENT = 'Holds a user\'s evaluation of a candidate\'s fulfillment of each qualification';

ALTER TABLE `Message` COMMENT = 'Holds general site messages, such as to content to email a user when they\'re added to a position';

ALTER TABLE `UserAuth` ADD FOREIGN KEY (`ua_u_id`) REFERENCES `User` (`u_id`);

ALTER TABLE `UserAuth` ADD FOREIGN KEY (`ua_uap_id`) REFERENCES `UserAuthProvider` (`uap_id`);

ALTER TABLE `LocalAuth` ADD FOREIGN KEY (`la_id`) REFERENCES `UserAuth` (`ua_providerId`);

ALTER TABLE `RoleForPosition` ADD FOREIGN KEY (`rfp_u_id`) REFERENCES `User` (`u_id`);

ALTER TABLE `RoleForPosition` ADD FOREIGN KEY (`rfp_p_id`) REFERENCES `Position` (`p_id`);

ALTER TABLE `RoleForPosition` ADD FOREIGN KEY (`rfp_r_id`) REFERENCES `Role` (`pre_id`);

ALTER TABLE `Candidate` ADD FOREIGN KEY (`c_cs_id`) REFERENCES `CandidateStatus` (`cs_id`);

ALTER TABLE `Candidate` ADD FOREIGN KEY (`c_p_id`) REFERENCES `Position` (`p_id`);

ALTER TABLE `CandidateStatus` ADD FOREIGN KEY (`cs_cse_id`) REFERENCES `CandidateStatusEnum` (`cse_id`);

ALTER TABLE `CandidateStatus` ADD FOREIGN KEY (`cs_u_id`) REFERENCES `User` (`u_id`);

ALTER TABLE `CandidateFiles` ADD FOREIGN KEY (`cf_c_id`) REFERENCES `Candidate` (`c_id`);

ALTER TABLE `CandidateRoundNote` ADD FOREIGN KEY (`crn_c_id`) REFERENCES `Candidate` (`c_id`);

ALTER TABLE `CandidateRoundNote` ADD FOREIGN KEY (`crn_r_id`) REFERENCES `Round` (`r_id`);

ALTER TABLE `Qualification` ADD FOREIGN KEY (`q_p_id`) REFERENCES `Position` (`p_id`);

ALTER TABLE `Round` ADD FOREIGN KEY (`r_p_id`) REFERENCES `Position` (`p_id`);

ALTER TABLE `Feedback` ADD FOREIGN KEY (`f_u_id`) REFERENCES `User` (`u_id`);

ALTER TABLE `Feedback` ADD FOREIGN KEY (`f_c_id`) REFERENCES `Candidate` (`c_id`);

ALTER TABLE `Feedback` ADD FOREIGN KEY (`f_r_id`) REFERENCES `Round` (`r_id`);

ALTER TABLE `FeedbackFiles` ADD FOREIGN KEY (`ff_f_id`) REFERENCES `Feedback` (`f_id`);

ALTER TABLE `QualificationForRound` ADD FOREIGN KEY (`qfr_r_id`) REFERENCES `Round` (`r_id`);

ALTER TABLE `QualificationForRound` ADD FOREIGN KEY (`qfr_q_id`) REFERENCES `Qualification` (`q_id`);

ALTER TABLE `FeedbackForQual` ADD FOREIGN KEY (`ffq_f_id`) REFERENCES `Feedback` (`f_id`);

ALTER TABLE `FeedbackForQual` ADD FOREIGN KEY (`ffq_q_id`) REFERENCES `Qualification` (`q_id`);

ALTER TABLE `FeedbackForQual` ADD FOREIGN KEY (`ffq_fqe_id`) REFERENCES `QualificationStatusEnum` (`qse_id`);

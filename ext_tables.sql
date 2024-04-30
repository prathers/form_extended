#
# Table structure for table 'tx_formdoubleoptin_domain_model_optin'
#
CREATE TABLE tx_formextended_domain_model_optin
(

	encoded_values  text,
	email           varchar(255)        DEFAULT ''  NOT NULL,
	is_validated    tinyint(4) unsigned DEFAULT '0' NOT NULL,
	validation_hash varchar(255)        DEFAULT ''  NOT NULL,
	validation_date int(11) unsigned    DEFAULT '0' NOT NULL,

	KEY hash (validation_hash)
);

CREATE TABLE tt_content
(
	site_sender varchar(255) DEFAULT '' NOT NULL
);

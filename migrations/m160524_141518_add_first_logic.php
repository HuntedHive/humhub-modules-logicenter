<?php

class m160524_141518_add_first_logic extends EDbMigration
{
	public function up()
	{
		HSetting::SetText("logic_enter", 'IF teacher_type = "math math2" and teacher_level = "level" and subject_area = "math math2" and email_domain = "edu.au" THEN insert into "Welcome Space, default, some-some"');
		HSetting::SetText("logic_else",'Welcome Space, default, some, callback');
	}

	public function down()
	{
		echo "m160524_141518_add_first_logic does not support migration down.\n";
		return false;
	}

	/*
	// Use safeUp/safeDown to do migration with transaction
	public function safeUp()
	{
	}

	public function safeDown()
	{
	}
	*/
}
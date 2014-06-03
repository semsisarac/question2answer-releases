<?php

	class qa_html_theme extends qa_html_theme_base
	{
		function nav_user_search()
		{
			$this->search();
			$this->nav('user');
		}
	}
	
?>
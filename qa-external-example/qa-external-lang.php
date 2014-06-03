<?php

/*
	Question2Answer 1.2.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-external-example/qa-external-lang.php
	Version: 1.2.1
	Date: 2010-07-29 03:54:35 GMT
	Description: Example of how to use your own language translation layer


	This software is free to use and modify for public websites, so long as a
	link to http://www.question2answer.org/ is displayed on each page. It may
	not be redistributed or resold, nor may any works derived from it.
	
	More about this license: http://www.question2answer.org/license.php


	THIS SOFTWARE IS PROVIDED "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL
	THE COPYRIGHT HOLDER BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
	SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
	TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
	PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
	LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
	NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

/*
	=======================================================================
	THIS FILE ALLOWS YOU TO USE YOUR EXISTING LANGUAGE TRANSLATION SOLUTION
	=======================================================================

	It is used if QA_EXTERNAL_LANG is set to true in qa-config.php.
*/

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}


	function qa_lang($identifier)
/*
	Provide the appropriate translation for the phrase labelled $identifier.

	If you cannot provide a translation, you can return qa_lang_base($identifier)
	which will use the default translation code for the engine.
*/
	{
		$gottranslation=false;
		
		if ($gottranslation)
			return 'the translation';
		else
			return qa_lang_base($identifier);
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
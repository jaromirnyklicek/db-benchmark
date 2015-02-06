<?php

interface IFormData
{
	const MEMBER_SEPARATOR = '->';

	function setData($data);


	function getData();


	function getColumnsMetadata();

}

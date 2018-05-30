<?php



/**
 * Skeleton subclass for representing a row from one of the subclasses of the 's3object' table.
 *
 * 
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 * @package    propel.generator.plugins.cpAwsPropelPlugin.lib.model
 */
class S3Document extends S3Object {

	/**
	 * Constructs a new S3Document class, setting the type column to S3ObjectPeer::CLASSKEY_1.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setType(S3ObjectPeer::CLASSKEY_1);
	}

} // S3Document

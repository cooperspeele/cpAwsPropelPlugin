<?php
/*
 * Skeleton subclass for representing a row from one of the subclasses of the 's3object' table.
 *
 * 
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory. *
 */
class S3Image extends S3Object {

	/**
	 * Constructs a new S3Image class, setting the type column to S3ObjectPeer::CLASSKEY_2.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setType(S3ObjectPeer::CLASSKEY_2);
	}

 protected function updateFileInfo($path) {
    parent::updateFileInfo($path);
    list($this->width, $this->height, $type, $attr) = getimagesize($path);
  }

  public function getS3ThumbnailPath() {
    return '/';
  }

  protected function doUploadFile(AmazonS3 $s3, $path, $filename) {
    // create thumbnail
    if ($thumbnail = $this->createThumbnail($path)) {
      $response = $s3->create_object(
        $this->getBucket(),
        $this->getS3ThumbnailPath() . $filename,
        array(
          'fileUpload' => $thumbnail,
          'acl' => AmazonS3::ACL_PRIVATE));
      unlink($thumbnail);
      if (!$response->isOK()) {
        throw new S3_Exception('Check your AWS settings, file was not uploaded successfully.');
      }
    }
  }

  protected function doDelete(AmazonS3 $s3) {
    if ($s3->if_object_exists(
      $this->getBucket(),
      $this->getS3ThumbnailPath() . $this->getFilename())) {

      $s3->delete_object(
        $this->getBucket(),
        $this->getS3ThumbnailPath() . $this->getFilename()
      ); // delete old file
    }
  }

  public function getThumbnailUrl() {
    $s3 = new AmazonS3(array(
      'key' => $this->getAccessKeyId(),
      'secret' => $this->getSecretAccessKey()
    ));

    return $s3->get_object_url(
      $this->getBucket(),
      $this->getS3ThumbnailPath() . $this->getFilename(),
      $this->getPreauth()
    );
  }

  /**
  * @return string thumbnail path
  *
  * $path - the path in which to create the thumbnail on S3
  * $width - the width of the thumbnail
  * $height - the height of the thumbnail. 0 means preserve the original proportions of the image.
  *
  * if $height = 0, the thumbnail's proportions will depend on the original ratio of width/height:
  * if original width > original height, then set the thumbnail width to the specified value; the
  *    actual thumbnail height will be less than this value to preserve the proportions of the original image.
  * if original width < original height, then set the thumbnail height to the specified $width value;
  *    the actual thumbnail width will be less than this value to perseve the propeertions of the original image.
  */
  protected function createThumbnail($path, $width = 120, $height = 0) {
    //check if images not video
    $imageinfo = getimagesize($path);
    if (!$imageinfo) {
      return null;
    }
    list($w, $h, $type, $attr) = $imageinfo;
    $ext = image_type_to_mime_type($type);
    if (!in_array($ext, array("image/jpeg", "image/png", "image/gif"))) {
      return null;
    }

    // create resource and determine size
    switch($ext) {
      case "image/jpeg":
        $orig_image = imagecreatefromjpeg($path);
        break;
      case "image/png":
        $orig_image = imagecreatefrompng($path);
        break;
      case "image/gif":
        $orig_image = imagecreatefromgif($path);
        break;
    }
    $ox = imagesx($orig_image);
    $oy = imagesy($orig_image);

    if (!$height) {
      if ($ox > $oy) {
        $nx = $width;
        $ny = floor($oy * ($nx / $ox));
      }
      else {
        $ny = $width;
        $nx = floor($ox * ($ny / $oy));
      }
    }
    else {
      $nx = $width;
      $ny = $height;
    }

    $new_image = imagecreatetruecolor($nx, $ny);
    if (!imagecopyresized($new_image, $orig_image, 0, 0, 0, 0, $nx, $ny, $ox, $oy)) {
      return null;
    }
    imagedestroy($orig_image);

    // put resource in temp file
    $thumbnail_path = tempnam(sys_get_temp_dir(), 'php');
    switch($ext) {
      case "image/jpeg":
        imagejpeg($new_image, $thumbnail_path);
        break;
      case "image/png":
        imagepng($new_image, $thumbnail_path);
        break;
      case "image/gif":
        imagegif($new_image, $thumbnail_path);
        break;
    }
    return $thumbnail_path;
  }
} // S3Image

<?php

/**
 * LICENSE: Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * PHP version 5
 *
 * @category  Microsoft Azure
 * @package   Yii-Azure
 * @author    Giuliano Iacobelli <giuliano.iacobelli@stamplay.com>
 * @copyright 2012 Stamplay
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link      http://www.windowsazure.com/en-us/develop/php/how-to-guides/blob-service/
 */
 
 
Yii::setPathOfAlias('WindowsAzure', Yii::getPathOfAlias('ext.yii-azure.lib'));			

class YiiAzure extends CApplicationComponent {

	// set the consumer key and secret
    public $config = array();
    
    /**
     * @var string Storage Account name
     */    
    public $storageAccountName;

    /**
     * @var string Storage Account key
     */    
    public $storageAccountKey;

    /**
     * @var string protocol
     */    
    public $protocol;
        
    /**
     * @var bool instance of the SendGrid library
     */
    private $_blobRestProxy;


	/**
	 * Returns services settings declared in the authorization classes.
	 * For perfomance reasons it uses Yii::app()->cache to store settings array.
	 * @return array services settings.
	 */
	public function getConfig() {
		if (Yii::app()->hasComponent('cache'))
			$config = Yii::app()->cache->get('azure.config');
		if (!isset($config) || !is_array($config)) {
			$config = array();
			foreach ($this->config as $configElem => $value) {
				$config[$configElem] = $value;
			}
			if (Yii::app()->hasComponent('cache'))
				Yii::app()->cache->set('azure.config', $config);
		}
		return $config;
	}
	        

	/**
	* Returns a BlobRestProxy object, this lets you operate with your storage.
	* This Object let's you create, manage and delete Containers, Block Blob and Page Blob. 
	* @return BlobRestProxy object.
	*/
    private function getBlobRestProxy() {
	    
		$connectionString ='DefaultEndpointsProtocol='.$this->protocol.';'
							.'AccountName='.$this->storageAccountName.';'							
							.'AccountKey='.$this->storageAccountKey;

		// Create blob REST proxy.					
		$blobRestProxy = WindowsAzure\Common\ServicesBuilder::getInstance()->createBlobService($connectionString);	
		
		return $blobRestProxy;	    
    }


    /**
    * This method creates a new block blob on a given container 
    * located in the current storage account.
    * @param string             $container The name of the container.
    * @param string             $blob_name The name of the blob.
    * @param string|resource    $content   The content of the blob.
    * @param Models\CreateBlobOptions $options   The optional parameters.
    *
    * @return CopyBlobResult
    */
	public function createBlockBlob($container, $blob_name, $content, $options=null) {

		$blobRestProxy = $this->getBlobRestProxy();
				
		try {
		    //Upload blob
		    $CopyBlobResult = $blobRestProxy->createBlockBlob($container, $blob_name, $content, $options);
		    $result = $CopyBlobResult->getETag();
		}
		catch(ServiceException $e){
			 $this->handleError($e);
		}	
		
		return $result;
	}
	

    /**
     * Reads or downloads a blob from the system, including its metadata and 
     * properties.
	 * In order to save your file on disk you can access the 
	 * resource stream with $blob->getContentStream()
     * file_put_contents($new_file_path, $blob->getContentStream());
     *
     * @param string                $container name of the container
     * @param string                $blob      name of the blob
     * @param Models\GetBlobOptions $options   optional parameters
     * 
     * @return Models\GetBlobResult
     */	
	public function getBlob($container, $blob, $options = null) {
		
		$blobRestProxy = $this->getBlobRestProxy();		
		
		try {
		    // Get blob.
		    $blob = $blobRestProxy->getBlob($container, $blob, $options);
		    return $blob;
		}
		catch(ServiceException $e){
			$this->handleError($e);
		}		
	}	

	/**
    * This method rename an existing block blob on a given container 
    * located in the current storage account.
    * @param string             $source_container The name of the source container.
    * @param string             $source_name The name of the source blob.
    * @param string             $destination_container The name of the destination container.
    * @param string             $destination_name The name of the destination blob.
    *
    * @return CopyBlobResult
    */
	function renameBlob($source_container, $source_name, $destination_container, $destination_name) {

		$blobRestProxy = $this->getBlobRestProxy();

        try {

            $blobRestProxy->copyBlob($destination_container, $destination_name, $source_container, $source_name);

            $blobRestProxy->deleteBlob($source_container, $source_name);
        }

        catch(ServiceException $e) {
             $this->handleError($e);
        }

        return true;
    }

    /**
     * Deletes a blob or blob snapshot.
     * @param string                   $container name of the container
     * @param string                   $blob      name of the blob
     * @param Models\DeleteBlobOptions $options   optional parameters
     * 
     * @return none
     */	
	public function deleteBlob($container, $blobName, $options = null) {

		$blobRestProxy = $this->getBlobRestProxy();		

		try {
		    // Delete container.
		    $blobRestProxy->deleteBlob($container, $blobName, $options);
		}
		catch(ServiceException $e){
			 $this->handleError($e);
		}							
	}
	
    /**
     * Lists all of the blobs in the given container.
     * 
     * After retrieving you can iterate over blob array
     * foreach($blobs as $blob)
	 * 		echo $blob->getName().": ".$blob->getUrl();
     *
     * @param string                  $container The container name.
     * @param Models\ListBlobsOptions $options   The optional parameters.
     * 
     * @array Blobs
     */	
	public function listBlobs($container, $options = null) {
		
		$blobRestProxy = $this->getBlobRestProxy();		
				
		try {
		    // List blobs.
		    $blob_list = $blobRestProxy->listBlobs($container, $options);
		    $blobs = $blob_list->getBlobs();
		    return $blobs;
		}
		catch(ServiceException $e){
			 $this->handleError($e);
		}
	}

	/**
     * Copies a source blob to a destination blob within the same storage account.
     * 
     * @param string $destinationContainer name of the destination  	container
     * @param string $destinationBlob      name of the destination  	blob
     * @param string $sourceContainer      name of the source  			container
     * @param string $sourceBlob           name of the source 			blob
     * @param Models\CopyBlobOptions $options              optional parameters
     * 
     * @return CopyBlobResult
     * 
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd894037.aspx
     */
    public function copyBlob(
        $destinationContainer, 
        $destinationBlob,
        $sourceContainer, 
        $sourceBlob, 
        $options = null
    ) {
		$blobRestProxy = $this->getBlobRestProxy();		
				
		try {
		    // List blobs.
			return $blobRestProxy->copyBlob(
				$destinationContainer, 
				$destinationBlob,
				$sourceContainer, 
				$sourceBlob, 
				$options = null
			);
		}
		catch(ServiceException $e){
			 $this->handleError($e);
		}
    }

    /**
     * Creates a new container in the given storage account.
     * When creating a container, you can set options on the container, but doing so is not required. 
     * 
     * @param string            $container The container name.
     * @param array 			$options   The optional parameters.
     * 
     * @return none
     */	
	public function createContainer($name, $metadata=array()) {
		
		$blobRestProxy = $this->getBlobRestProxy();		
		
		// OPTIONAL: Set public access policy and metadata.
		// Create container options object.
		$createContainerOptions = new WindowsAzure\Blob\Models\CreateContainerOptions(); 
		
		// Set public access policy. Possible values are 
		// PublicAccessType::CONTAINER_AND_BLOBS and PublicAccessType::BLOBS_ONLY.
		// CONTAINER_AND_BLOBS:     
		// Specifies full public read access for container and blob data.
		// proxys can enumerate blobs within the container via anonymous 
		// request, but cannot enumerate containers within the storage account.
		//
		// BLOBS_ONLY:
		// Specifies public read access for blobs. Blob data within this 
		// container can be read via anonymous request, but container data is not 
		// available. proxys cannot enumerate blobs within the container via 
		// anonymous request.
		// If this value is not specified in the request, container data is 
		// private to the account owner.
		$createContainerOptions->setPublicAccess(WindowsAzure\Blob\Models\PublicAccessType::CONTAINER_AND_BLOBS);
		
		// Set container metadata
		foreach($metadata as $key=>$value)
			$createContainerOptions->addMetaData($key, $value);
		
		try {
		    // Create container.
		    $response = $blobRestProxy->createContainer($name, $createContainerOptions);
		    
		}
		catch(ServiceException $e){
			 $this->handleError($e);
		}
		
	}


    /**
     * Creates a new container in the given storage account.
     * 
     * @param string                        $container The container name.
     * @param Models\DeleteContainerOptions $options   The optional parameters.
     * 
     * @return none
     * 
     */		
	public function deleteContainer($container, $options = null) {		
		
		$blobRestProxy = $this->getBlobRestProxy();		
		
		try {
		    // Delete container.
		    $blobRestProxy->deleteContainer($container);
		}
		catch(ServiceException $e){
			 $this->handleError($e);
		}
	}
	
	
	private function handleError($e) {
	    // Handle exception based on error codes and messages.
	    // Error codes and messages are here: 
	    // http://msdn.microsoft.com/en-us/library/windowsazure/dd179439.aspx	
	    $code = $e->getCode();
	    $error_message = $e->getMessage();		    
	    Yii::log($code.": ".$error_message,CLogger::LEVEL_ERROR,'ext.yii-azure');		
	    
	}	
	
} 

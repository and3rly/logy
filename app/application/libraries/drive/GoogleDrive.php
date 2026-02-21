<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

class GoogleDrive {
	
	private $cliente;
	private $servicio;
	protected $subcarpeta = 'Varios';
	protected $carpeta = '12lhxAEIBouXWIYgrVAiGMeW4ex02EzkV';

	public function __construct()
	{
		$this->cliente = new Google_Client();
		$this->cliente->addScope(Google_Service_Drive::DRIVE);
		$this->cliente->setAuthConfig(APPPATH."libraries/drive/cliente.json");
		$this->cliente->setAccessType('offline');
        $this->cliente->setPrompt('select_account consent');
        $this->cliente->setIncludeGrantedScopes(true); 

		$this->servicio = new Google_Service_Drive($this->cliente);		
	}	

	public function set_subcarpeta($nombre)
    {
        $this->subcarpeta = $nombre;
    }

	public function subirArchivo($args=[]) {

        $file = new Google_Service_Drive_DriveFile([
        	'name' => $args["name"],
        	'parents' => array($this->getCarpeta($this->subcarpeta))
        ]);

        $tmp = $this->servicio->files->create($file, [
			"data"       => $args["tmp_name"],
			"mimeType"   => $args["type"],
			"uploadType" => "multipart"
        ]);

        $this->setPermiso([
        	"id" => $tmp->id,
        	"type" => "anyone",
        	"role" => "commenter"
        ]);

        return $tmp->id;
      
    }

    public function setPermiso($args=[]) {
        $this->servicio->getClient()->setUseBatch(true);

        try {
            $batch = $this->servicio->createBatch();

            $userPermission = new Google_Service_Drive_Permission(array(
                'type' => $args['type'],
                'role' => $args['role']
            ));
            
            $request = $this->servicio->permissions->create(
                $args['id'], 
                $userPermission, 
                array('fields' => 'id')
            );

            $batch->add($request, 'anyone');
            $batch->execute();
        } finally {
            $this->servicio->getClient()->setUseBatch(false);
        }
    }

    public function setCarpeta($nombre)
    {
        $fileMetadata = new Google_Service_Drive_DriveFile([
	    	'name' => $nombre,
	    	'parents' => array($this->carpeta),
	    	'mimeType' => 'application/vnd.google-apps.folder'
		]);

		$file = $this->servicio->files
		->create($fileMetadata,['fields' => 'id']);

		return $file->id;
    }

    public function getCarpeta($carpeta)
    {
        $pageToken = null;
        
        do {
            $response = $this->servicio->files->listFiles([
                'q'         => "name='{$carpeta}'",
                'spaces'    => 'drive',
                'pageToken' => $pageToken,
                'fields'    => 'nextPageToken, files(id, name)',
            ]);

            foreach ($response->files as $file) {
                if ($file->name == $carpeta) {
                    return $file->id;
                }
            }
           
            $pageToken = $response->pageToken;
        } while ($pageToken != null);

        return $this->setCarpeta($carpeta);
    }

    public function getArchivo($id, $args=[])
    {
        return $this->servicio->files->get($id, $args);
    }
}

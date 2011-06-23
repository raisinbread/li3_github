<?php

namespace li3_github\extensions\adapter\data\source\http;

use \lithium\util\String;

/**
 * Lithium GitHub Data Source.
 *
 * @package li3_github\extensions\adapter\data\source\http
 * @author John Anderson
 */
class GitHub extends \lithium\data\source\Http {
	
	/**
	 * Class dependencies.
	 */
	protected $_classes = array(
		'service' => 'lithium\net\http\Service',
		'entity' => 'lithium\data\entity\Document',
		'set' => 'lithium\data\collection\DocumentSet',
	);
	
	/**
	 * Constructor.
	 *
	 * @param array $config Configuration options.
	 */
	public function __construct(array $config = array()) {
		if(!empty($config['token'])) {
			$login = $config['login'] . '/token';
			$password = $config['token'];
		} else {
			$login = $config['login'];
			$password = $config['password'];
		}
		$defaults = array(
			'adapter'  => 'GitHub',
			'token'    => null,
			'scheme'   => 'https',
			'auth'     => 'Basic',
			'version'  => '1.1',
			'port'     => 443,
			'basePath' => '/api/v2/json',
		);
		$config['host']     = 'github.com';
		$config['login']    = $login;
		$config['password'] = $password;
		parent::__construct($config + $defaults);
	}
	
	/**
	 * Data source READ operation.
	 *
	 * @param string $query 
	 * @param array $options 
	 * @return mixed
	 */
	public function read($query, array $options = array()) {
		extract($query->export($this));
		$path = '';
		switch ($source) {
			case 'issues':
				$path = String::insert(
					'/issues/list/{:user}/{:repo}/{:state}',
					array(
						'user'  => urlencode($conditions['user']),
						'repo'  => urlencode($conditions['repo']),
						'state' => urlencode($conditions['state']),
					)
				);
			break;
		}
		$data = json_decode($this->connection->get($this->_config['basePath'] . $path), true);
		return $this->item($query->model(), $data[$source], array('class' => 'set'));
	}
	
	/**
	 * Used for object formatting.
	 *
	 * @param string $entity 
	 * @param array $data 
	 * @param array $options 
	 * @return mixed
	 */
	public function cast($entity, array $data, array $options = array()) {
		foreach($data as $key => $val) {
			if (!is_array($val)) {
				continue;
			}
			$class = 'entity';
			$model = $entity->model();
			$data[$key] = $this->item($model, $val, compact('class'));
		}
		return parent::cast($entity, $data, $options);
	}

	/**
	 * Data Source CREATE operation.
	 *
	 * @param string $query 
	 * @param array $options 
	 * @return mixed
	 */
	public function create($query, array $options = array()) {
		extract($query->export($this));
		$path = '';
		switch ($source) {
			case 'issues':
				extract($query->entity()->data());
				$path = String::insert(
					'/issues/open/{:user}/{:repo}',
					array(
						'user'  => urlencode($user),
						'repo'  => urlencode($repo),
					)
				);
				$data = compact('title', 'body');
			break;
		}
		$result = json_decode($this->connection->post($this->_config['basePath'] . $path, $data), true);
		return isset($return[$source]);
  }
}
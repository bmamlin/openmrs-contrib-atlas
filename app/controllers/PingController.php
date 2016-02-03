<?php

/**
 * Ping controller.
 *
 */
class PingController extends BaseController {


	/**
	 * Delete Ping function
	 * Deprecated - Maintain compatibility with Atlas Module 1.x
	 */
	public function pingDelete()
	{    

		$id = Input::get('id');
		$user = Session::get(user);
		$date = new \DateTime;
		$site = DB::table('atlas')->where('id','=', $id)->first();

		if ($site != null) {
			DB::table('archive')->insert(array(
				'archive_date' => $date, 
				'site_uuid' => $site->id, 
				'id' => Uuid::uuid4()->toString(), 
				'type' => $site->type,
				'longitude' =>  $site->longitude, 
				'latitude' =>  $site->latitude,
				'name' =>  $site->name, 
				'url' =>  $site->url, 
				'image' =>  $site->image, 
				'contact' =>  $site->contact, 
				'changed_by' =>  'module:' . $_SERVER['REMOTE_ADDR'], 
				'patients' =>  $site->patients, 
				'encounters' =>  $site->encounters, 
				'observations' =>  $site->observations, 
				'notes' =>  $site->notes, 
				'email' => $site->email,
				'data' =>  $site->data, 
				'show_counts' => $site->show_counts,
				'atlas_version' => $site->atlas_version,
				'date_created' => $site->date_created,
				'created_by' => $site->created_by));
			DB::table('auth')->where('atlas_id', '=', $id)->delete();
			DB::table('atlas')->where('id', '=', $id)->delete();
			Log::info("Deleted ".$deleteId." from ".$_SERVER['REMOTE_ADDR']);

			return 'DELETED';
		}
	}

	/**
	 * Post Ping function
	 * Deprecated - Maintain compatibility with Atlas Module 1.x
	 */
	public function pingPost()
	{
		$this->createTableIfNotPresent();

		Log::debug("DATA received: " . Request::getContent());

		$json = json_decode(Request::getContent(), true);
		$date = new \DateTime;
		$id['id'] = $json['id'];

		$openmrs_version = (empty($json['data'])) ? "" : $json['data']['version'];
		$param = array(
			'id' => $json['id'],
			'latitude' => floatval($json['geolocation']['latitude']),
			'longitude' => floatval($json['geolocation']['longitude']),
			'name' => $json['name'],
			'url' => $json['url'],
			'type' => $json['type'],
			'image' => $json['image'],
			'openmrs_version' => $openmrs_version,
			'patients' => intval($json['patients']),
			'encounters' => intval($json['encounters']),
			'observations' => intval($json['observations']),
			'contact' => $json['contact'],
			'email' => $json['email'],
			'notes' => $json['notes'],
			'data' => json_encode($json['data']),
			'atlas_version' => $json['atlasVersion'],
			'date_created' => $date);

		

		$site = DB::table('atlas')->where('id','=', $param['id'])->first();
		if ($site != null) {
			DB::table('archive')->insert(array(
				'archive_date' => $date, 
				'site_uuid' => $site->id, 
				'id' => Uuid::uuid4()->toString(), 
				'type' => $site->type,
				'longitude' =>  $site->longitude, 
				'latitude' =>  $site->latitude,
				'name' =>  $site->name, 
				'url' =>  $site->url, 
				'image' =>  $site->image, 
				'contact' =>  $site->contact, 
				'changed_by' => 'module:' . $_SERVER['REMOTE_ADDR'], 
				'patients' =>  $site->patients, 
				'encounters' =>  $site->encounters, 
				'observations' =>  $site->observations, 
				'notes' =>  $site->notes, 
				'email' => $site->email,
				'data' =>  $site->data, 
				'action' =>  'UPDATE', 
				'openmrs_version' => $openmrs_version, 
				'data' =>  $site->data,
				'show_counts' => $site->show_counts,
				'atlas_version' => $site->atlas_version,
				'date_created' => $site->date_created,
				'created_by' => $site->created_by));

			unset($param['date_created']);
			DB::table('atlas')->where('id', '=', $site->id)->update($param);
			Log::debug("Updated ".$param['id']." from ".$_SERVER['REMOTE_ADDR']);
		} else {
			 // new implementation
			DB::table('atlas')->insert($param);
			//insert into archive
			$param['action'] = "ADD";
			$param['site_uuid'] = Uuid::uuid4()->toString();
			$param['archive_date'] = $date;
			DB::table('archive')->insert($param);
			
			Log::debug("Created ".$param['id']." from ".$_SERVER['REMOTE_ADDR']);
		}
		return 'SUCCES';
	}

	/**
	 * Post Ping function - Handle Auto from Atlas Module 2.0
	 *
	 */
	public function autoPostModule()
	{
		$this->createTableIfNotPresent();
		Log::debug("DATA received: " . Request::getContent());
		$json = json_decode(Request::getContent(), true);
		$date = new \DateTime;
		$module = $json['id'];
		Log::info('Module uuid: ' . $module);
		$siteM = DB::table('auth')->where('token','=', $module)->first();
		if ($siteM == NULL)
			App::abort(403, 'Unauthorized');

		$openmrs_version = (empty($json['data'])) ? "" : $json['data']['version'];
		$param = array(
			'id' => $siteM->atlas_id,
			'patients' => intval($json['patients']),
			'encounters' => intval($json['encounters']),
			'observations' => intval($json['observations']),
			'openmrs_version' => $openmrs_version, 
			'data' => json_encode($json['data']),
			'atlas_version' => $json['atlasVersion'],
			'date_created' => $date);

		$site = MarkerSite::find($param['id']);

		if(!$site){
			Log::debug("Site not found: ".$param['id']." from ".$_SERVER['REMOTE_ADDR']);
			return;
		}

		$site->update($param);
		Log::debug("Updated ".$param['id']." from ".$_SERVER['REMOTE_ADDR']);

		$distroService = new DistributionService();
		$authService = new AuthorizationService();
		$auditService = new AuditService($distroService, $authService);
		$auditService->auditModuleSite($site, $module);

		return 'SUCCES';
	}

	/**
	 * Post Ping function - Handle Ping from Atlas Module 2.0
	 * Never Used and deprecated
	 */
	public function pingPostModule()
	{
		$this->createTableIfNotPresent();
		Log::debug("DATA received: " . Request::getContent());
		$json = json_decode(Request::getContent(), true);
		$date = new \DateTime;
		$module = $json['id'];
		Log::info('Module uuid: ' . $module);
		$siteM = DB::table('auth')->where('token','=', $module)->first();
		if ($siteM == NULL)
			App::abort(403, 'Unauthorized');

		$param = array(
			'id' => $siteM->atlas_id,
			'patients' => intval($json['patients']),
			'encounters' => intval($json['encounters']),
			'observations' => intval($json['observations']),
			'atlas_version' => $json['atlasVersion'],
			'date_created' => $date);

		$site = DB::table('atlas')->where('id','=', $param['id'])->first();
		if ($site != null) {
			DB::table('archive')->insert(array(
				'archive_date' => $date, 
				'site_uuid' => $site->id, 
				'id' => Uuid::uuid4()->toString(), 
				'action' =>  'UPDATE',  
				'type' => $site->type,
				'longitude' =>  $site->longitude, 
				'latitude' =>  $site->latitude,
				'name' =>  $site->name, 
				'url' =>  $site->url, 
				'image' =>  $site->image, 
				'contact' =>  $site->contact, 
				'changed_by' => 'module:' . $_SERVER['REMOTE_ADDR'], 
				'patients' =>  $site->patients, 
				'encounters' =>  $site->encounters, 
				'observations' =>  $site->observations, 
				'openmrs_version' => $openmrs_version,
				'notes' =>  $site->notes, 
				'email' => $site->email,
				'data' =>  $site->data,
				'show_counts' => $site->show_counts, 
				'atlas_version' => $site->atlas_version,
				'date_created' => $site->date_created));

			unset($param['date_created']);
			DB::table('atlas')->where('id', '=', $site->id)->update($param);
			Log::debug("Updated ".$param['id']." from ".$_SERVER['REMOTE_ADDR']);
		} else {
			Log::debug("Site not found: ".$param['id']." from ".$_SERVER['REMOTE_ADDR']);
		}
		return 'SUCCES';
	}

	public function pingAtlasDelete() {
		$id = Input::get('id');
		$user = Session::get(user);
		$date = new \DateTime;
		$site = MarkerSite::find($id);
		$privileges = DB::table('auth')->where('token','=', $user->uid)->where('atlas_id','=', $param['id'])
		->where('privileges', '=', 'ALL')->first();

		if ($user->role == 'ADMIN' && $privileges == NULL) {
			$privileges = new Authorization(array('token' => $user->uid,
	  				'principal' => 'admin:' . $user->uid,
	  				'privileges' => 'ADMIN'));
		}
		Log::debug("Privileges: " . $privileges->principal . "/" . $privileges->privileges);

		if ($site != null) {

			$distroService = new DistributionService();
			$authService = new AuthorizationService();
			$auditService = new AuditService($distroService, $authService);
			$auditService->auditDeletedSite($site);

			DB::table('auth')->where('atlas_id', '=', $id)->delete();
			DB::table('atlas')->where('id', '=', $id)->delete();
			Log::info("Deleted ".$id." from ".$_SERVER['REMOTE_ADDR']);

			$existingDistribution = Distribution::find($site->distribution);
			if($existingDistribution && !$existingDistribution->is_standard){
				DB::table('distributions')->where('id', '=', $existingDistribution->id)->delete();
			}
		}
	}

	public function createTableIfNotPresent()
	{
		if ( !Schema::hasTable('atlas') || !Schema::hasTable('admin') 
			|| !Schema::hasTable('auth') || !Schema::hasTable('archive'))
			
			Artisan::call('migrate', ['--path'=> "app/database/migrations"]);
			Log::info('Database Updated');
	}

}

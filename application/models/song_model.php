<?php

class Song_model extends CI_Model
{
	
	public static $tbl_name = 'tbl_track';
	public static $tbl_name_cont = 'tbl_content';
	public static $tbl_name_pop = 'tbl_track_recommend';
	public static $mod_id=20;
	function __construct()
	{
		parent::__construct();
	}
	
	// get sortorder >> maximum sortorder from the records
	function getSortOrderPop($tbl_name_pop="tbl_popular_song")
	{
		$this->db->select_max('position');
		$q = $this->db->get($tbl_name_pop);
	    $r = $q->row();
		return intval($r->position)+1;
	}
	function find_by_id($content_id=0)
	{
		return $this->db->get_where(self::$tbl_name, array('content_id'=>$content_id), 1, 0);
	}
	
	function getRecords($mod_id=0,$popular=0,$sort="sortorder", $trash_filter=true, $trash_cond=0, $limit=50, $offset=null)
	{
		$this->db->order_by($sort.' desc'); 
		
		// prepare condition..
		$cond = array('module_id' => $mod_id);
		if($trash_filter == true){
			$cond['del_flag'] = $trash_cond;
		}
		if($popular!=0){
			$cond[$popular] = 1;
		}
		if($popular=="featured"){
			$tbl_name_pop='tbl_track_recommend';
		}
		if($popular=="slideshow"){
			$tbl_name_pop='tbl_track_download_free';
		}
		else if($popular=="homepage"){
			$tbl_name_pop='tbl_homepage_song';
		}
		else{
			$tbl_name_pop=	self::$tbl_name_pop;
		}
		$offset = ($offset) ? $offset : 0;


		$this->db->join($tbl_name_pop, ''.self::$tbl_name_cont.'.content_id = '.$tbl_name_pop.'.content_id', 'inner');
		$this->db->where($cond);
		$q= $this->db->get(self::$tbl_name_cont, $limit, $offset);
		//new dbug($q->result());
		return $q;
	}
	function getSearchRecords($mod_id=0, $keyword='', $trash_filter=true, $trash_cond=0, $limit=50, $offset=null)
	{
		$keyword = trim($keyword);
		$this->db->order_by('sortorder desc'); 
		$this->db->like('title', $keyword);
		$this->db->or_like('full_content', $keyword);
		
		// prepare condition..
		$cond = array('module_id' => $mod_id);
		if($trash_filter == true){
			$cond['del_flag'] = $trash_cond;
		}
		
		$offset = ($offset) ? $offset : 0;
		
		$this->db->join(self::$tbl_name, ''.self::$tbl_name_cont.'.content_id = '.self::$tbl_name.'.content_id', 'inner');
		$this->db->where($cond);
		$q= $this->db->get(self::$tbl_name_cont, $limit, $offset);
		
		
		//$q = $this->db->get_where(self::$tbl_name, $cond, $limit, $offset);
		return $q;
	}
	public function getAllEvents($module_id=0)
	{
		$s = "SELECT * ";
		//$s.= ", c.posted_date AS posted_date, tbl_content.full_content AS full_content ";
		$s.= "FROM tbl_content as c LEFT JOIN tbl_track as e ON c.content_id = e.content_id ";
		$s.= "WHERE (c.status <> 0 AND c.del_flag = 0) AND (c.module_id={$module_id}) ORDER BY sortorder DESC ";
		
		return $this->db->query($s);
	}
	
	
	
	public function getSlideshow($limit=10, $offset=0)
	{
		$s = "SELECT c.content_id AS contentid, c.title, c.module_id, i.content_id AS i_content_id, i.image ";
		$s.= "FROM tbl_content AS c JOIN tbl_image AS i ";
		$s.= "ON c.content_id=i.content_id ";
		$s.= "WHERE c.status=1 AND c.del_flag=0 AND i.image <> '' AND c.slideshow=1 ";
		// allow news and article module's image for slideshow.
		$s.= "AND (c.module_id=2 OR c.module_id=3) ";
		$s.= "ORDER BY c.sortorder DESC ";
		$s.= "LIMIT {$limit} OFFSET {$offset}";
		return $this->db->query($s);
	}
	
	/*
	* -- Returns the total number of rows
	*/
	public function count_all($mod_id=0, $trash_filter=true, $trash_cond=0, $status_filter=false, $status=1,$regular="0",$types="past",$day=NULL)
	{
		// prepare condition..
		$cond = array(self::$tbl_name_cont.'.module_id' => $mod_id);
		if($trash_filter == true){
			$cond[self::$tbl_name_cont.'.del_flag'] = $trash_cond;
		}
		if($status_filter == true){
			$cond[self::$tbl_name_cont.'.status'] = $status;
		}
		if($regular==1){
			$cond[self::$tbl_name.'.regular'] = 1;
		}
		else{
			$cond[self::$tbl_name.'.regular != '] = 1;
			}
		if($types=="past"){
			$cond[self::$tbl_name.'.date_from < '] = todaydate();
		}
		if($types=="upcoming"){
			$cond[self::$tbl_name.'.date_from > '] = todaydate();
		}
		if($day!=NULL){
			$cond[self::$tbl_name.'.day'] = $day;
		}
		$this->db->join(self::$tbl_name, ''.self::$tbl_name_cont.'.content_id = '.self::$tbl_name.'.content_id', 'inner');
		
		$this->db->where($cond);
		return $this->db->count_all_results(self::$tbl_name_cont);
	}
	
	// FRONTEND methods
	public function get_list($mod_id=0, $popular_filter=false, $popular=1, $limit=5, $offset=null)
	{
		$this->db->order_by('sortorder desc'); 
		$cond = array('module_id' => $mod_id, 'status'=>1);
		if($popular_filter == true){
			$cond['popular'] = $popular;
		}
		
		$offset = ($offset) ? $offset : 0;
		$q = $this->db->get_where(self::$tbl_name, $cond, $limit, $offset);
		return $q;
	}
	public function getnewupc($mod_id=9,$table_name="tbl_track",$fld="upc"){
		$sql="select max(".$fld.") as maxupc from ".$table_name." where upc NOT LIKE 'D%'";
		$q=$this->db->query($sql);
		$r=$q->row();
		
		$rupc=@$r->maxupc+1;

		return $rupc;
	}
	public function count_all_pop($mod_id=0,$popular="0", $trash_filter=true, $trash_cond=0, $status_filter=false, $status=1)
	{
		// prepare condition..
		$cond = array(self::$tbl_name_cont.'.module_id' => $mod_id);
		if($trash_filter == true){
			$cond[self::$tbl_name_cont.'.del_flag'] = $trash_cond;
		}
		if($status_filter == true){
			$cond[self::$tbl_name_cont.'.status'] = $status;
		}
		if($popular==1){
			$cond[self::$tbl_name_cont.'.featured'] = 1;
		}
		else{
			$cond[self::$tbl_name_cont.'.featured != '] = 1;
			}
	
		$this->db->join(self::$tbl_name_pop, ''.self::$tbl_name_cont.'.content_id = '.self::$tbl_name_pop.'.content_id', 'inner');
		
		$this->db->where($cond);
		return $this->db->count_all_results(self::$tbl_name_cont);
	}
	public function getgenre($content_id=0,$class="input medium form_tip", $selected = false){
		if($selected){
			$genre= $selected;
		}else{
			$q=self::find_by_id($content_id);
			$r=$q->row();
			$genre=@$r->genre;
		}
		$l='<select name="genre" id="genre" class="'.$class.'"><option value="">Select Genre</option>';
		$arrgenre=array("Jazz","Rock","Hip Hop","Pop","Latin","Opera","Sound Track");		
		foreach($arrgenre as $val){
			$sel="";
			if($genre==$val){
				$sel='selected="selected"';
			}
        $l.='<option '.$sel.' value="'.$val.'">'.$val.'</option>';
		}
      $l.='</select>';
	  return $l;
	}
	
	
	function get_track_list($mod_id=20, $perpage=0, $offset=0,$sortorder="c.title"){
		$sql="SELECT
				  c.content_id, c.title, ART.content_id AS artist_cid, ART.slug AS artist_slug, t.artist_display, ac.title AS album_title, c.posted_date, t.content_id AS tid, i.image, i.image_path, i.ver1,
				  ac.content_id as album_content_id, R.total_votes, R.total_value, R.used_ips 
				FROM tbl_content AS c
				  INNER JOIN tbl_track AS t ON t.content_id = c.content_id
				  INNER JOIN tbl_album AS a ON t.album_content_id = a.content_id
				  INNER JOIN tbl_content AS ac ON ac.content_id = a.content_id
				  LEFT JOIN tbl_artist_in_content AIC ON ac.content_id = AIC.content_id 
				  LEFT JOIN tbl_content ART ON AIC.artist_content_id = ART.content_id AND ART.del_flag = 0 AND ART.status = 1 	
				  LEFT JOIN tbl_image AS i ON ac.content_id = i.content_id
				  LEFT JOIN tbl_rating R ON c.content_id = R.content_id 
				WHERE c.module_id = {$mod_id}
					AND c.del_flag = 0
					AND c.status = 1
					AND ac.del_flag = 0
					AND ac.status = 1
				GROUP BY c.content_id
				ORDER BY {$sortorder} asc";
			if($perpage!=0){	
				$sql.=" LIMIT {$perpage}";
				$sql.=" OFFSET {$offset}
				";	
			}
		
		$q=$this->db->query($sql);
		return $q;
	}
	function get_track_search_list($mod_id=20, $perpage=0, $offset=0,$sortorder="c.title",$song_search_tit,$song_search_art,$genre){
		$sql="SELECT
				  c.content_id,
				  c.title,
				  ART.content_id AS artist_cid, 
				  t.artist_display,
				  ART.slug AS artist_slug,
				  ac.title         AS album_title,
				  c.posted_date,
				  t.content_id     AS tid,
				  i.image,
				  i.image_path,
				  i.ver1,
				  ac.content_id as album_content_id,
				  R.total_votes, R.total_value, R.used_ips 
				FROM tbl_content AS c
				  INNER JOIN tbl_track AS t ON t.content_id = c.content_id
				  INNER JOIN tbl_album AS a ON t.album_content_id = a.content_id
				  INNER JOIN tbl_content AS ac ON ac.content_id = a.content_id
				  LEFT JOIN tbl_artist_in_content AIC ON ac.content_id = AIC.content_id 
				  LEFT JOIN tbl_content ART ON AIC.artist_content_id = ART.content_id AND ART.del_flag = 0 AND ART.status = 1 	
				  LEFT JOIN tbl_image AS i ON ac.content_id = i.content_id
				  LEFT JOIN tbl_rating R ON c.content_id = R.content_id 	
				WHERE c.module_id = {$mod_id}
					AND c.del_flag = 0
					AND c.status = 1
					AND ac.del_flag = 0
					AND ac.status = 1";
			if($song_search_tit!=""){
				$sql.=" AND c.title like '%".$song_search_tit."%'";		
			}
			if($song_search_art!=""){
				$sql.=" AND t.artist_display like '%".$song_search_art."%'";		
			}
			if($genre!=""){
				$sql.=" AND t.genre='".$genre."'";		
			}
			$sql.=" GROUP BY c.content_id
				ORDER BY {$sortorder} asc";
			if($perpage!=0){	
				$sql.=" LIMIT {$perpage}";
				$sql.=" OFFSET {$offset}
				";	
			}
		
		$q=$this->db->query($sql);
		return $q;
	}
	
}
<?
////////////////////////////////////////////////////////////////////////////
//						디자인파일을 html 로 변환하는 프로그램
////////////////////////////////////////////////////////////////////////////
$cnt_post = count($_POST);

$GLOBALS['auth'] = array();												// 기타 레이어, 권한설정을 저장한다.
$GLOBALS['JS_CODE'] = array();											// 코드값만 저장한다 (인자값없이 한번만 나오면 되는 함수들)
$GLOBALS['JS'] = $GLOBALS['JQS'] = array();							// 자바스크립트, jquery 내용을 저장한다.
$GLOBALS['_CA_'] = $GLOBALS['JS_SUMBIT'] = '';						// 열람중인 게시물 컨텐츠, submit 시 추가할 스크립트 모음
$GLOBALS['ETC_CODE'] = $GLOBALS['ETC'] = array();					// 기타 레이어 코드값만 저장한다.
$GLOBALS['view_pages'] = $GLOBALS['u_viewer_list'] = array();	//	게시판 전체 목록을 쿼리하는 경우 보기페이지 링크를 저장함.
$GLOBALS['body_out_tag'] = $GLOBALS['mfd'] = '';					// 사용자정의 회원필드 (library_insiter.class.php 멤버함수에서 세팅됨)
$GLOBALS['TB_IDX'] = $GLOBALS['TB_IDX_L'] = $GLOBALS['BD_INFO'] = $GLOBALS['FORM_CONFIG'] = array();	// 테이블인덱스, 게시판DB정보, <table>게시판정보
$GLOBALS['AT_INFO_ONE'] = $GLOBALS['AT_INFO_REL'] = array();	// 단일 게시물정보, 관련 게시물정보
$GLOBALS['SDL'] = $GLOBALS['MB_INFO'] = array();					// 회원정보들
$GLOBALS['FLAG'] = $GLOBALS['chg_head'] = array();
$GLOBALS['rept_tr_depth'] = $GLOBALS['rept_table_depth'] = 0;
$GLOBALS['web_page_title'] = $GLOBALS['web_page_meta_desc'] = $GLOBALS['web_page_meta_kw'] = '';
$GLOBALS['dialog_size'] = $GLOBALS['site_config']['dialog_size_p'];
$GLOBALS['buffer_script'] = $GLOBALS['buffer_style_file'] = $GLOBALS['buffer_style'] = array();
$GLOBALS['unveil'] = 'N';
$buffer_final = '';
if ($_SESSION['_VM_'] === 'm') $GLOBALS['dialog_size'] = $GLOBALS['site_config']['dialog_size_m'];

$import_files_size = 0;														// 임포트된 회수
$import_files = $import_lines = array();								// 임포트된 파일, 라인들을 저장 (치환문자열에 의한 임포트를 위해 임포트 명령을 유지해 둠)

if ($cnt_post > 0) {
	include "{$DIRS['include_root']}form_input_filter.inc.php";
	$GLOBALS['post_to_get_qs'] = http_build_query(array_filter($post_to_get));
} else {
	$GLOBALS['post_to_get_qs'] = '';
}

ob_start();

$speed_print = 'N';
$GLOBALS['w_microtime'] = $GLOBALS['lib_common']->get_microtime();	// 시작시간 기록

//if ($_SERVER['REQUEST_METHOD'] !== 'GET') $GLOBALS['lib_insiter']->get_var_filter();

// IP차단기능 (전체 또는 과트래픽, 로봇제외 과트래픽 차단인 경우)
if (($GLOBALS['site_config']['use_ip_block'] !== 'N') && $user_info['user_level'] > $GLOBALS['site_config']['admin_level']) include "{$DIRS['visit']}include/visit_block_process.inc.php";

if ($_GET['design_file'] == '') {
	$design_file = $GLOBALS['site_config']['homepage'];	// db.inc.php, config.inc.php 파일은 프로그램 전 영역에서 공유됨. 디자이너 디버깅시 문제가 발생하므로 디자인 파일 'home.php' 파일은 뷰어에서만 디폴트로 설정함
	$site_page_info = $GLOBALS['lib_fix']->get_site_page_info($design_file);
} else {
	$design_file = $_GET['design_file'];
}

// SSL 전체 적용 설정이고 SSL 접속이 아닌경우
if ($GLOBALS['site_config']['ssl_port'] != '' && $_SERVER['HTTPS'] !== 'on' && $GLOBALS['site_config']['protocol'] === 'https' && $user_info['id'] !== $GLOBALS['site_config']['s_admin']) {
	$T_ssl_domain = $GLOBALS['site_config']['ssl_domain'];				// 인증서 도메인
	if ($T_ssl_domain == '') $T_ssl_domain = $_SERVER['SERVER_NAME'];	// 없으면 웹 서버의 대표도메인
	if ($T_ssl_domain === $PU_host['host']) {	// 인증서도메인과 www.인증서도메인 이 아닌 경우 ssl 적용 안함 (m. 도메인등의 인증서 오류를 방지하기 위함)
		if ($_SERVER['REDIRECT_URL'] != '') $https_url = $_SERVER['REDIRECT_URL'];
		else $https_url = $https_url = $_SERVER['REQUEST_URI'];
		header("Referer: {$_SERVER['HTTP_REFERER']}");
		header("Location: https://{$T_ssl_domain}:{$GLOBALS['site_config']['ssl_port']}{$https_url}");
		exit;
	}
}

if ($site_page_info['file_name'] == '') {
	header("HTTP/1.0 301 Moved Permanently");
	header('Location: ' . $GLOBALS['lib_insiter']->get_abs_dir_add($GLOBALS['lib_insiter']->get_sol_url('error.php', array())));
	//$GLOBALS['lib_common']->meta_url($root, 2, "Moved Permanently. <a href=\"#\" onclick=\"document.location.href='/'\">[Go Home, Thank You^^!]</a>");
}

// 페이지 열람권한 확인
if ($_GET['ABPPW'] != '') $_SESSION['ss_ab_ppw'] = $GLOBALS['lib_common']->get_mcrypt_encrypt('', $_GET['ABPPW']);				// 저장용 패스워드가 넘어오면 세션에 저장
else if ($_POST['ABPPW'] != '') $_SESSION['ss_ab_ppw'] = $GLOBALS['lib_common']->get_mcrypt_encrypt('', $_POST['ABPPW']);
if ($_GET['passwd'] != '' || $_POST['passwd'] != '') $_SESSION['ss_ab_ppw'] = '';															// 일반 패스워드가 입력된 경우 저장용 페이지열람 패스워드 삭제
$GLOBALS['lib_insiter']->get_page_auth($site_page_info, $user_info);
if ($_GET['search_value2'] != '') $_GET['search_value'] = $_GET['search_value2'];
if ($user_info['user_level'] > $GLOBALS['site_config']['admin_level']) $GLOBALS['lib_insiter']->design_file_count($design_file);	// 페이지뷰 증가

// 외부연결로 프린트레이아웃이 지정된 경우 해제
if ($_GET['OTSKIN'] === 'layout_ptr.php' && $referer_parse['host'] != '' && stripos($referer_parse['host'], $PU_host['host']) === false) $_GET['OTSKIN'] = '';

$article_copy_cover = '';
if ($_GET['is_cpy_article'] === 'Y') $article_copy_cover = ' set-cover';

// 디자이너 미리보기용 - 임포트, 템플릿 등 직접 접근할 필요가 없는 페이지 중 레이아웃이 설정되지 않은 페이지는 빈레이아웃 설정
if (strpos($_SERVER['HTTP_REFERER'], 'page_file=' . $site_page_info['file_name']) !== false && $user_info['user_level'] <= $GLOBALS['site_config']['admin_level'] && $_GET['AJAX'] != 'Y' && $site_page_info['skin_file'] == '' && stripos($site_page_info['tag_header'], '<!doctype') === false && ($site_page_info['type'] === 'I' || $site_page_info['type'] === 'T' || $site_page_info['type'] === 'B')) $_GET['OTSKIN'] = 'layout_bl.php';

if ($_GET['OTSKIN'] == '') {															// 원타임 레이아웃이 없는경우
	if ($_SESSION['_VM_'] === 'p') {
		$var_idx_ssf = 'ssf';
		$var_idx_ssus = 'user_skin_file';
		$var_idx_scsf = 'skin_file';
	} else {
		$var_idx_ssf = 'ssf_m';
		$var_idx_ssus = 'user_skin_file_m';
		$var_idx_scsf = 'skin_file_m';
	}
	if (isset($_GET[$var_idx_ssf])) $_SESSION[$var_idx_ssus] = $_GET[$var_idx_ssf];	// 세션 레이아웃파일 정보가 넘어오면($_GET 변수로)
	if ($site_page_info['skin_file'] != '') {										// 레이아웃설정된 페이지인경우
		if ($site_page_info['skin_file'] === 'default') {
			if ($_SESSION[$var_idx_ssus] != '') $skin_file = $_SESSION[$var_idx_ssus];	// 세션 레이아웃 적용
			else $skin_file = $GLOBALS['site_config'][$var_idx_scsf];
		} else {
			$skin_file = $site_page_info['skin_file'];							// 아니면 내부 레이아웃 적용
		}
	}
} else {																						// 원타임 레이아웃이 있는 경우
	if ($_GET['AJAX'] !== 'Y') {														// AJAX 호출이 아닌 경우 적용 (OTSKIN 과 AJAX=Y 가 함께 넘어오면 OTSKIN 비활성됨)
		$skin_file = $_GET['OTSKIN'];
		$_GET['AJAX'] = '';
	}
}
if ($skin_file != '' && $_GET['AJAX'] == '') {									// 현재 페이지에 레이아웃이 있는 경우
	$site_skin_page_info = $GLOBALS['lib_fix']->get_site_page_info($skin_file);	// 레이아웃 파일을 불러 출력하다가 콘텐츠 명령을 만나면 다시 현재 파일을 불러 삽입한다.
	if ($site_skin_page_info['file_name'] == '') $GLOBALS['lib_common']->die_msg('skin file error - viewer');	// 스킨파일 없음 오류 (보안상)
	$GLOBALS['lib_insiter']->get_page_auth($site_skin_page_info, $user_info);
	if ($site_skin_page_info['is_admin'] === 'Y' && $site_page_info['is_admin'] !== 'Y') {
		$url_edit = $GLOBALS['lib_insiter']->get_sol_url('page_designer.php', array('change_vars'=>array('page_file'=>$site_page_info['file_name'])));
		$GLOBALS['lib_common']->alert_url("사용자용 페이지에 관리자용 레이아웃이 적용되어 있습니다.\\n편집화면으로 이동합니다. 관리자용 페이지로 변경하거나 레이아웃을 변경하세요.", 'E', $url_edit);
	}
	$design = $GLOBALS['lib_fix']->design_load_viewer($DIRS, $site_skin_page_info['file_name'], $site_skin_page_info, $site_page_info);
	$tag_info = $site_skin_page_info;
	if ($site_page_info['tag_body'] != '') {
		if (stripos($site_page_info['tag_body'], '<body') !== false) $tag_info['tag_body'] = $site_page_info['tag_body'];		// 실제 페이지의 body 값 우선
		else $tag_info['tag_body'] .= $site_page_info['tag_body'];
	}
	if (preg_match("|<title[^>]*>(.*)</title[^>]*>|is", $tag_info['tag_header'], $skin_page_title)) $GLOBALS['web_page_title'] = $skin_page_title[1];												// 레이아웃의 <title>
	if (preg_match("|<meta name=\"description\" content=\"([^\"]+)\" />|is", $tag_info['tag_header'], $skin_page_meta_desc)) $GLOBALS['web_page_meta_desc'] = $skin_page_meta_desc[1];	// 레이아웃의 <meta desc>
	if (preg_match("|<meta name=\"keywords\" content=\"([^\"]+)\" />|is", $tag_info['tag_header'], $skin_page_meta_kw)) $GLOBALS['web_page_meta_kw'] = $skin_page_meta_kw[1];				// 레이아웃의 <meta keywords>
	if ($site_page_info['tag_header'] != '') {																									// 실제 페이지 header 등을 더함
		$site_page_info['tag_header'] = str_ireplace('<head>', '', $site_page_info['tag_header']);
		$site_page_info['tag_header'] = str_ireplace('</head>', '', $site_page_info['tag_header']);
		if (preg_match("|<title[^>]*>(.*)</title[^>]*>|is", $site_page_info['tag_header'], $site_page_title)) {				// 페이지의 <title> 존재 확인
			$GLOBALS['web_page_title'] = $site_page_title[1];																					// 페이지의 title 값 세팅
			if (count($skin_page_title) > 0) {																										// 레이아웃의 <title> 존재 확인
				$GLOBALS['web_page_title'] = str_ireplace('__TITLE__', $skin_page_title[1], $GLOBALS['web_page_title']);		// 레이아웃의 title 치환
				$site_page_info['tag_header'] = trim(str_replace($site_page_title[0], '', $site_page_info['tag_header']));	// 페이지의 <title> 제거
			}
		}
		if (preg_match("|<meta name=\"description\" content=\"([^\"]+)\" />|is", $site_page_info['tag_header'], $site_page_meta_desc)) {		// 페이지의 <meta desc> 존재 확인
			$GLOBALS['web_page_meta_desc'] = str_ireplace('__TITLE__', $GLOBALS['web_page_title'], $site_page_meta_desc[1]);						// 페이지의 meta desc 값 세팅
			$site_page_info['tag_header'] = trim(str_replace($site_page_meta_desc[0], '', $site_page_info['tag_header']));							// 페이지의 <meta desc> 제거
		}
		if (preg_match("|<meta name=\"keywords\" content=\"([^\"]+)\" />|is", $site_page_info['tag_header'], $site_page_meta_kw)) {			// 페이지의 <meta kw> 존재 확인
			$GLOBALS['web_page_meta_kw'] = str_ireplace('__TITLE__', $GLOBALS['web_page_title'], $site_page_meta_kw[1]);							// 페이지의 meta kw 값 세팅
			$site_page_info['tag_header'] = trim(str_replace($site_page_meta_kw[0], '', $site_page_info['tag_header']));							// 페이지의 <meta kw> 제거
		}
		if ($site_page_info['tag_header'] != '') $tag_info['tag_header'] = str_ireplace('</head>', "{$site_page_info['tag_header']}\n</head>", $tag_info['tag_header']);
	}
	if ($site_page_info['tag_body_out'] != '') {
		if (stripos($tag_info['tag_body_out'], '</body>') !== false) $tag_info['tag_body_out'] = str_replace('</body>', $site_page_info['tag_body_out'] . '</body>', $tag_info['tag_body_out']);
		else $tag_info['tag_body_out'] .= "\n{$site_page_info['tag_body_out']}";
	}
	if ($site_page_info['page_lock'] != '') $tag_info['page_lock'] = $site_page_info['page_lock'];
} else {																																						// 레이아웃이 없는경우
	$design = $GLOBALS['lib_fix']->design_load_viewer($DIRS, $design_file, $site_page_info);
	$tag_info = $site_page_info;
	if ($_GET['AJAX'] === 'Y') $tag_info['tag_header'] = $tag_info['tag_body'] = $tag_info['tag_body_out'] = $tag_info['page_lock'] = '';
	if (preg_match("|<title[^>]*>(.*)</title[^>]*>|is", $tag_info['tag_header'], $site_page_title)) $GLOBALS['web_page_title'] = $site_page_title[1];	// 페이지의 title 값 세팅
	if (preg_match("|<meta name=\"description\" content=\"([^\"]+)\" />|is", $tag_info['tag_header'], $site_page_meta_desc)) $GLOBALS['web_page_meta_desc'] = str_ireplace('__TITLE__', $GLOBALS['web_page_title'], $site_page_meta_desc[1]);	// 페이지의 meta desc 값 세팅
	if (preg_match("|<meta name=\"keywords\" content=\"([^\"]+)\" />|is", $tag_info['tag_header'], $site_page_meta_kw)) $GLOBALS['web_page_meta_kw'] = str_ireplace('__TITLE__', $GLOBALS['web_page_title'], $site_page_meta_kw[1]);				// 페이지의 meta kw 값 세팅
}

echo '<pre>'; 
// print_r($site_page_info); 
ㅔ
echo '</pre>';

if ($GLOBALS['site_config']['ajax_link'] != '' && $skin_file != '' && $_GET['AJAX'] == 'Y') {
	if ($GLOBALS['site_config']['default_template'] != '') include $AB_builder_dir . 'program/include/title_ajax.inc.php';
}

if ($tag_info['tag_header'] != '') $header_tag = $tag_info['tag_header'] . "\n";	// 레이아웃이 있는 경우 레이아웃에 적용된 헤드 태그 적용(실제 페이지 헤더는 'CONT' 명령에서 출력)
$open_body_tag = $tag_info['tag_body'];
if ($tag_info['tag_body_in'] != '') $body_in_tag = $tag_info['tag_body_in'] . "\n";
$GLOBALS['body_out_tag'] .= $tag_info['tag_body_out'];
$tag_body_out_import = '';																			// 임포트용

if ($tag_info['page_lock'] != '' && $user_info['user_level'] > $GLOBALS['site_config']['admin_level']) {	// 내용보호
	$header_tag .= $GLOBALS['lib_common']->page_lock($tag_info['page_lock']);
	$page_lock_array = array();
	if (strpos($tag_info['page_lock'], ';D;') !== false) $page_lock_array[] = 'onselectstart="return false" ondragstart="return false"';
	if (strpos($tag_info['page_lock'], ';R;') !== false) $page_lock_array[] = 'oncontextmenu="return false"';
	if (strpos($tag_info['page_lock'], ';K;') !== false) $page_lock_array[] = 'onkeydown="return false" onkeyup="return false"';
	if (count($page_lock_array) > 0) $open_body_tag = str_replace('>', ' ' . implode(' ', $page_lock_array) . '>', $open_body_tag);
}

$TAB_IDX = 0;
$exp = $delete_tags = array();

foreach ($PV_view_include_list as $T_proc_idx=>$T_proc_file_name) if (file_exists($T_proc_file_name)) include_once $T_proc_file_name;		// 추가 프로세스 (private_info.inc.php 에서 변수정의 하고 해당파일 사용자 구현)

if ($VP_BASE_TAG === 'Y') {			// 가상페이지인 경우 base dir 을 변경
	$VP_BASE_TAG = "<base href='" . substr($GLOBALS['lib_insiter']->get_abs_dir_add('/'), 0, -1) . "'>";
	$header_tag = str_ireplace('<head>', "<head>\n{$VP_BASE_TAG}", $header_tag);
}

if ($GLOBALS['is_bot'] !== 'N' || $user_info['user_level'] <= $GLOBALS['site_config']['admin_level']) {												// 관리자인 경우 로그분석 패스
	$header_tag = str_replace('<script type="text/javascript" src="/ga.js"></script>' . "\r\n", '', $header_tag);
	$header_tag = preg_replace("|<script[^>]*>[^<]+google-analytics[^<]+</script>|is", '', $header_tag);
	$GLOBALS['site_config']['log_script_1'] = $GLOBALS['site_config']['log_script_2'] = $GLOBALS['site_config']['log_script_3'] = '';
}

if ($_GET['AJAX'] !== 'Y') {			// 처음 호출한 페이지에서만 세팅할 자바스크립트 변수들 정의
	$header_tag = str_replace('</head>', "<script type=\"text/javascript\">
	var _VM_ = '{$_SESSION['_VM_']}';
	var pretty_url = '{$GLOBALS['site_config']['pretty_url']}';
	var admin_theme = '{$GLOBALS['site_config']['admin_thema']}';
	var mobile_device = '{$GLOBALS['site_config']['mobile_device']}';
	var global_dialog_size = '{$GLOBALS['dialog_size']}';
	var user_level = '{$user_info['user_level']}';
	var price_sosu = '{$GLOBALS['site_config']['price_sosu']}';
	var price_unit = '{$GLOBALS['site_config']['price_unit']}';
	var price_unit_loc = '{$GLOBALS['site_config']['price_unit_loc']}';
	var price_chg_1won = '{$GLOBALS['site_config']['price_chg_1won']}';
	var _afo_ = {'cmt_reply':[], 'cmt_reply_btn':[], 'cmt_proc_num':'', 'cmt_reply_depth':''};		// 새로고침 후에도 ajax 로딩 영역을 유지하기 위한 변수
	var post_to_get_qs = '{$GLOBALS['post_to_get_qs']}';
	var submit_is_ing = 'N';
</script>
</head>", $header_tag);
}
echo($GLOBALS['lib_insiter']->reserv_replace($header_tag . $T_script_vars_once . $open_body_tag . $body_in_tag, '', $user_info));	// 헤더태그 출력

$skip_info = array('page_menu'=>$site_page_info['menu'], 'design_file'=>$site_page_info['file_name'], 'udf_group'=>$site_page_info['udf_group'], 'page_type'=>$site_page_info['type']);

if ($speed_print === 'Y') {
	$T_time_total = 0;
	$time_check = array();
}
for ($i_viewer=0,$cnt_viewer=count($design); $i_viewer<$cnt_viewer; $i_viewer++) {
	if ($speed_print === 'Y') $T_time_start = $GLOBALS['lib_common']->get_microtime();																// 시작시간 기록(디버깅용)
	if ($save_var_tag_idx[1] == '') $save_var_tag_idx = array();
	if ($GLOBALS['site_config']['use_tab'] === 'Y') {																			// 탭문자 삽입 모드
		$is_print_tab = 'Y';
		if ($exp['0'] === 'CONTO' || $exp['0'] === 'IMG' || $exp['0'] === 'BUTT' || $exp['0'] === 'TAG' || $exp['0'] === 'ART' || $exp['0'] === 'MEM') $is_print_tab = 'N';	// 이전 출력중 몇 가지 다음에는 탭출력안함
		$exp = $GLOBALS['lib_insiter']->explode_sol_div($GLOBALS['DV']['dv'], $design[$i_viewer]);															// 현재 명령줄분리, 몇 가지 다음에는 탭출력안함 (!! 이전출력을 사용하기 위해 여기서 분리함)
		if ($exp['0'] === 'CONTO' || $exp['0'] === 'IMG' || $exp['0'] === 'BUTT' || $exp['0'] === 'TAG' || $exp['0'] === 'ART' || $exp['0'] === 'MEM') $is_print_tab = 'N';
		$exp_next = $GLOBALS['lib_insiter']->explode_sol_div($GLOBALS['DV']['dv'], $design[$i_viewer+1]);													// 다음 명령줄분리, 몇 가지 에는 현재출력에 줄바꿈 안함
		if ($exp['0'] === 'CONTO' || $exp_next['0'] === 'IMG' || $exp_next['0'] === 'BUTT' || $exp_next['0'] === 'TAG' || $exp_next['0'] === 'ART' || $exp_next['0'] === 'TDC') $td_nl = '';
		else $td_nl = "\n";
		if ($TAB_IDX > 0) {																												// 탭출력
			if (in_array($exp['0'], $VG_layout_commands) && strpos($exp['0'], 'C') !== false) $TAB_IDX = $TAB_IDX - 1;
			for ($i_table_index=0; $i_table_index<$TAB_IDX; $i_table_index++) if ($is_print_tab === 'Y') print_method("\t", $save_var_tag_idx);
		}
	} else {
		$exp = $GLOBALS['lib_insiter']->explode_sol_div($GLOBALS['DV']['dv'], $design[$i_viewer]);															// 명령줄분리
	}

	if ($exp['0'] == '') continue;

	// 항목노출 여부결정
	// $exp['14']는 치환해서 넘기지 말것 (치환된 값에 노출제한 구분자가 있으면 문제됨)
	if ($exp['14'] != '' && !in_array($exp['0'], $VG_layout_commands)) if ($GLOBALS['lib_insiter']->is_skip($design, $exp['14'], $user_info['user_level'], $i_viewer, '', $skip_info) !== 'VIEW') continue;

	if ($exp['0'] === 'TAG') {
		$saved_text = str_replace(chr(92).r.chr(92).n, "\n", $exp['1']);		// 업데이트/검증 후 제거할 부분
		$saved_text = str_replace(chr(92).n, "\n", $saved_text);
		$saved_text = $GLOBALS['lib_insiter']->replace_include_string($saved_text);
		$component_view = str_replace("{$GLOBALS[site_config][replace_str_open_close][0]}레이어{$GLOBALS[site_config][replace_str_open_close][1]}", '', $saved_text);
		$component_view = $GLOBALS['lib_insiter']->insert_blank($component_view, $exp['12'], $exp['13']);
		$component_view = $GLOBALS['lib_insiter']->reserv_replace($component_view, $form_config, $user_info, 'N', 'N');	// 내부변수치환
		print_method($component_view, $save_var_tag_idx);
		//continue;
	} else if ($exp['0'] === 'ART') {
		if (strpos($design[$i_viewer], 'ARTAfter') === false) $exp = $GLOBALS['lib_insiter']->reserv_replace($exp, $form_config, $user_info, 'N', 'Y', 'N', 'ARTBefore');
		//$exp = $GLOBALS['lib_insiter']->reserv_replace($exp, $form_config, $user_info, 'N', 'Y');
		$tag = make_article_value($board_info, $form_config, $exp, $user_info);
		if (isset($GLOBALS['renew_AV'])) {		// total_rep, total_rel 등 게시물 레코드에서 실시간으로 세팅되는 항목을 위해 임시 전역변수를 이용해서 재설정
			$T_article_value = @array_merge($form_config['article_value_one'], $GLOBALS['renew_AV']);
			$GLOBALS['AT_INFO_ONE']["{$T_article_value['board_name']}{$T_article_value['serial_num']}"] = $form_config['article_value_one'] = $T_article_value;
			unset($GLOBALS['renew_AV']);
		}
		foreach ($PV_board_article_process as $T_proc_idx=>$T_proc_file_name) if (file_exists($T_proc_file_name)) include_once $T_proc_file_name;
		$component_view = $GLOBALS['lib_insiter']->insert_blank($tag, $exp['12'], $exp['13']);
		$component_view = $GLOBALS['lib_insiter']->reserv_replace($component_view, $form_config, $user_info, 'N', 'N', 'N', 'ARTAfter');
		if ($save_var_tag_idx[0] == '' && $exp['15'] != '') $save_var_tag_idx[0] = $exp['15'];
		print_method($component_view, $save_var_tag_idx);
		//continue;
	} else if ($exp['0'] === 'BUTT') {
		// 버튼 출력부분		BUTT|list|IMG|design/images/btn.gif|width=100|target;name,20,20,20,20|style
		$exp = $GLOBALS['lib_insiter']->reserv_replace($exp, $form_config, $user_info, 'N');																						// 내부변수치환
		$tag = $GLOBALS['lib_insiter']->make_button($exp, $board_info, $form_config, $user_info, $i_viewer, $site_page_info);
		$component_view = $GLOBALS['lib_insiter']->insert_blank($tag, $exp['12'], $exp['13']);
		print_method($component_view, $save_var_tag_idx);
		//continue;
	} else if ($exp['0'] === 'TBLO' || $exp['0'] === 'DIVO') {
		//$exp = str_replace($GLOBALS['DV']['tdv'], $GLOBALS['DV']['dv'], $exp);
		if (preg_match('/save-var-name=(\'|")([^\'"]+)(\'|")/', $exp['2'], $matches)) $save_var_tag_idx = array($matches[2], $exp[1]);
		$T_exp_3 = $exp['3'];
		$exp['3'] = '';
		if (substr($exp['5'], 0, 16) === '{FC_board_name}:' && $GLOBALS['FORM_CONFIG']['last']['board_name'] != '') $exp['5'] = str_replace('{FC_board_name}', $GLOBALS['FORM_CONFIG']['last']['board_name'], $exp['5']);
		$exp = $GLOBALS['lib_insiter']->reserv_replace($exp, $form_config, $user_info, 'N', 'Y');	 													// 내부변수치환
		$form_pass = 'N';
		if ($exp['4'] != '') {
			$GLOBALS['TB_IDX'][] = $exp['1'];				// table 인덱스 저장
			if ($exp['4'] === 'TC_BOARD') {					// 테이블에 기능설정이 되어 있는지 확인한다.
				$GLOBALS['form_sub_query'] = array();		// 서브쿼리만 보관하기 위해 사용하는 변수
				$form_config = $GLOBALS['lib_fix']->get_board_config($exp['1'], $exp['5'], $exp['6'], $exp['7'], $design, $user_info);	// 게시판 출력 정보 추출
				$board_info = $GLOBALS['lib_fix']->get_board_info($form_config['board_name'], $form_config, '', 'Y', 'Y', 'Y');			// 게시판 DB 정보 추출
				if ($board_info['name'] == '') {				// 게시판이 존재하지 않을 때
					if ($_GET['_preview_ifrm_'] === 'Y') $form_pass = 'Y';	// 편집페이지에서 iframe 호출인 경우 메시지 노출 없이 폼 설정 패스
					else print_method('존재하지 않는 게시판입니다.', $save_var_tag_idx);
				}
				if ($form_pass === 'N') {
					if ($form_config['query_type'] !== 'R') $board_info_child = array();	// 연결게시판 처리 중이면 하위 게시판 정보 설정(예외 상황을 처리할 때 본 설정을 활용)
					else $board_info_child = $board_info['child'];
					$form_config['board_info'] = $board_info;
					switch ($form_config['page_type']) {
						case 'LIST' :								// 목록폼
							$form_config['auth_info'] = $GLOBALS['lib_insiter']->get_article_auth($board_info, array(), $user_info, 'list', '', $form_config);
							if ($form_config['auth_info'] !== 'O' && $GLOBALS['auth']['chk_auth_result'] !== 'O') $GLOBALS['lib_common']->alert_url("\'열람(LIST)\' 권한이 없습니다.", 'E', '', 'document', '', '1', 'Y');
							$form_config['form_name'] = board_form($form_config, $board_info, $_GET['prev_url']);
							if ($form_config['category_disabled'] !== 'D') {								// 쿼리무시가 아닌경우
								if (substr($form_config['user_query'], 0, 9) !== 'includes ') {		// 서브쿼리영역에 전체쿼리 프로그램 파일 존재 확인
									$query_viewer = $form_config['query'] = $GLOBALS['lib_insiter']->get_query($board_info, $form_config);			// 현재 보여 주어야할 내용에 적당한 쿼리를 얻음
									//echo($query_viewer . '<br><br>');
									if ($form_config['union_tables'] == '') {									// union 연결목록 아닌경우
										if ($form_config['join_table'] == '') $remove_dup = '';
										else $remove_dup = 'distinct ';
										if ($form_config['tpb'] > 1 || $form_config['tpa'] == 0) {
											$query_count = $GLOBALS['lib_common']->get_ppb_query($query_viewer/*, "select count({$remove_dup}{$board_info['tbl_name']}.{$board_info['fld_name_idx']})"*/);
											//echo($query_count . '<br><br>');
											$T_result = $GLOBALS['lib_common']->querying($query_count);
											$T_value = mysql_fetch_row($T_result);
											$form_config['total_record'] = /*$total_record = */$T_value['0'];
											mysql_free_result($T_result);
										} else {
											$form_config['total_record'] = /*$total_record = */$form_config['tpa'];
										}
									} else {																		// UNION 목록인 경우
										$exp_query_viewer = explode(' order by ', $query_viewer);
										if ($DB_TABLES['abp_instead_union'] == '') {
											$T_result = $GLOBALS['lib_common']->querying($exp_query_viewer[0]);
											$form_config['total_record'] = mysql_num_rows($T_result);
										} else {
											$T_result = $GLOBALS['lib_common']->querying(preg_replace('/^(select )(.*)( from)/', '\1count(*)\3', $exp_query_viewer[0]));
											list($form_config['total_record']) = mysql_fetch_array($T_result);
										}
										mysql_free_result($T_result);
									}
								} else {																			// 전체쿼리 프로그램이 인클루드 된 경우
									$inc_exp = $GLOBALS['lib_insiter']->explode_sol_div($GLOBALS['DV']['ct2'], substr($form_config['user_query'], 9));
									if ($inc_exp['1'] != '') {
										$var_include = $GLOBALS['lib_common']->parse_property($inc_exp['1'], ',', '=', '', 'N');
										foreach ($var_include as $key_inc=>$value_inc) $$key_inc = $value_inc;
									}
									if ($inc_exp['2'] == '') include $inc_exp['0'];
									else include_once $inc_exp['0'];
								}
								if ($form_config['page_block_page_var_name'] == '') $form_config['page_block_page_var_name'] = 'PB_' . (count($board_info_child) === 0 ? $board_info['create_date'] : $board_info_child['create_date']);
								if ($form_config['tpa'] > 0) {
									if ($_GET[$form_config['page_block_page_var_name']] <= 0) $current_page = 1;
									else $current_page = $_GET[$form_config['page_block_page_var_name']];
									$limit_start = $form_config['tpa'] * ($current_page-1);
									$limit_end = $form_config['tpa'];
									$query_limit = $form_config['query_limit'] = "$query_viewer limit $limit_start, $limit_end";
								} else {
									$current_page = 1;
									$query_limit = $form_config['query_limit'] = $query_viewer;
								}
								//echo($query_limit);
								$form_config['current_page'] = $current_page;
								$form_config['article_result'] = $GLOBALS['lib_common']->querying($query_limit);
								$form_config['repeat_number'] = $form_config['abs_repeat'] = mysql_num_rows($form_config['article_result']);		// 실제 반복될 개수
								if ($form_config['total_record'] == $form_config['tpa']) $form_config['total_record'] = /*$total_record = */$form_config['abs_repeat'];
								$form_config['repeat_number_rev'] = 1;
								$form_config['repeat_number_ttt'] = 0;
								$T_article_value = get_article_value($form_config, $board_info, $user_info);
								if ($T_article_value[$board_info['fld_name_idx']] != '' && count($board_info_child) > 0 && $board_info_child['template'] === 'comment') {		// 댓글의 수정,삭제,답변 버튼 권한을 적용하기 위함
									$GLOBALS['lib_insiter']->get_article_relation($T_article_value);
									$board_info['child'] = $GLOBALS['BD_INFO'][$board_info['child']['name']];
								}
								$GLOBALS['AT_INFO_ONE']["{$T_article_value['board_name']}{$T_article_value['serial_num']}"] = $form_config['article_value_one'] = $T_article_value;
								if ($_GET['SCROLLLOADING'] !== 'Y' && $form_config['tpl'] > 0) {
									$tpln = 'tpa_' . preg_replace('|[^0-9A-Za-z]|', '', $form_config['idx']);
									$load_url = $GLOBALS['lib_insiter']->get_sol_url($_SERVER['REQUEST_URI'], array('change_vars'=>array($form_config['page_block_page_var_name']=>'', $tpln=>'', 'tpa'=>'')));
									$exp['2'] = $GLOBALS['lib_common']->set_tag_attr($exp['2'], 'id', str_replace('index=', 'adx-', $form_config['idx']), 'ifnot');
									$exp['2'] = $GLOBALS['lib_common']->set_tag_attr($exp['2'], 'pvn', $form_config['page_block_page_var_name'], 'ifnot');
									$exp['2'] = $GLOBALS['lib_common']->set_tag_attr($exp['2'], 'curp', $form_config['current_page'], 'ifnot');
									$exp['2'] = $GLOBALS['lib_common']->set_tag_attr($exp['2'], 'tpl', $form_config['tpl'], 'ifnot');
									$exp['2'] = $GLOBALS['lib_common']->set_tag_attr($exp['2'], 'ttg', ceil($form_config['abs_repeat'] / $form_config['tpl']), 'ifnot');
									$exp['2'] = $GLOBALS['lib_common']->set_tag_attr($exp['2'], 'ttp', $form_config['tpa'] != '0' ? ceil($form_config['tpa'] / $form_config['tpl']) : '0', 'ifnot');
									$exp['2'] = $GLOBALS['lib_common']->set_tag_attr($exp['2'], 'url', $load_url, 'ifnot');
									$exp['2'] = $GLOBALS['lib_common']->set_tag_attr($exp['2'], 'tpln', $tpln, 'ifnot');
									$exp['2'] = $GLOBALS['lib_common']->set_tag_attr($exp['2'], 'dur', '250', 'ifnot');
									$exp['2'] = $GLOBALS['lib_common']->set_tag_attr($exp['2'], 'debug', 'N', 'ifnot');
									$exp['2'] = $GLOBALS['lib_common']->set_tag_attr($exp['2'], 'pla', '.-btnPageLink', 'ifnot');
									$exp['2'] = $GLOBALS['lib_common']->set_tag_attr($exp['2'], 'data-area', $form_config['query_type'] !== 'R' ? '.articleList .-tblList tbody' : '.ABA-cmt-list-wrap', 'ifnot');
									$exp['2'] = $GLOBALS['lib_common']->set_tag_attr($exp['2'], 'qt', $form_config['query_type'], 'ifnot');
									$tbl_idx = $GLOBALS['lib_common']->get_tag_attr($exp['2'], 'id');
									$GLOBALS['JQS'][] = "$('#{$tbl_idx}').evt_load_record({});";
								}
								if ($form_config['total_record'] > 0) {		// 전체페이지수 파악하고 존재하지 않는 페이지 호출시 오류
									if ((int)$form_config['tpa'] > 0) $form_config['total_page'] = ceil($form_config['total_record'] / $form_config['tpa']);
									else $form_config['total_page'] = '1';
								} else {
									 $form_config['total_page'] = '0';
								}
								//echo("호호호 : {$current_page} > {$form_config['total_page']} > {$_GET[$form_config['page_block_page_var_name']]} > {$form_config['total_record']}, {$form_config['tpa']}");
								if ($_GET['AJAX'] !== 'Y' && $_GET[$form_config['page_block_page_var_name']] != '' && $current_page > $form_config['total_page'] && $form_config['total_record'] > $form_config['tpa'] && $form_config['skip_form'] !== 'Y') {
									header("HTTP/1.0 404 Not Found");
									header('Location: ' . $GLOBALS['lib_insiter']->get_abs_dir_add($GLOBALS['lib_insiter']->get_sol_url('error.php', array('change_vars'=>array('msg'=>'pageurlnotfound')))));
								}
							}
						break;
						case 'VIEW' :																																				// 보기폼
							$form_config['article_value_one'] = $GLOBALS['lib_insiter']->get_article_one($form_config, $user_info, $board_info);
							$form_config['auth_info'] = $GLOBALS['lib_insiter']->get_article_auth($board_info, $form_config['article_value_one'], $user_info, 'view', '', $form_config);
							if ($form_config['auth_info'] !== 'O' && $GLOBALS['auth']['chk_auth_result'] !== 'O') {
								if ($site_page_info['view_err_page'] != '') {
									$page_info = $site_page_info;
									include "{$DIRS['include_root']}auth_process.inc.php";		// True 가 하나도 없는 경우 권한없음
								} else if ($site_page_info['perm_err_msg'] != '') {
									$err_msg = $site_page_info['perm_err_msg'];
								} else {
									$err_msg = "'열람(VIEW)' 권한이 없습니다.";
								}
								$err_msg = str_replace("\r\n", chr(92).r.chr(92).n, addslashes($err_msg));
								$GLOBALS['lib_common']->alert_url($err_msg, 'E', '', 'document', '', '1', 'Y');
							} else {
								if ($_GET['APV'] != '') {		// 비밀번호 링크 접속인 경우 보안을 위해 접속 후 패스워드 변경
									$modify_record = array();
									$modify_record['passwd'] = $GLOBALS['lib_insiter']->get_enc_passwd($GLOBALS['w_time'], 'B');
									$GLOBALS['lib_common']->modify_record($board_info['tbl_name'], $board_info['fld_name_idx'], $form_config['article_value_one'][$board_info['fld_name_idx']], $modify_record);
								}
							}
							$form_config['form_name'] = board_form($form_config, $board_info, $_GET['prev_url']);
							// 게시물 마다 다른 title, description, keywords 세팅 (1순위 게시판설정, 2순위 게시물설정 3순위 Navigation 설정)
							if (($form_config['chg_title'] === 'Y' || $form_config['chg_title'] === 'O') && ($board_info['view_page'] == $site_page_info['file_name'] || stripos($_SERVER['REQUEST_URI'], '_view.') !== false) && $form_config['article_value_one']['subject'] != '') {
								if ($board_info['meta_title'] == '') {
									if ($GLOBALS['chg_title'] == '') $GLOBALS['chg_title'] = strip_tags($form_config['article_value_one']['subject']) . ($form_config['chg_title'] === 'Y' ? " - {$GLOBALS['site_config']['site_name']} {$board_info['alias']}" : '');
									else $GLOBALS['chg_title'] = strip_tags($form_config['article_value_one']['subject']) . ($form_config['chg_title'] === 'Y' ? " - {$GLOBALS['chg_title']}" : '');
								} else {
									$GLOBALS['chg_title'] = $board_info['meta_title'] . ($form_config['chg_title'] === 'Y' ? " - {$GLOBALS['chg_title']}" : '');
								}
								if ($board_info['fn_meta_desc'] != '' && $form_config['article_value_one'][$board_info['fn_meta_desc']] != '') $GLOBALS['chg_description'] = $form_config['article_value_one'][$board_info['fn_meta_desc']];
								if ($board_info['fn_meta_kw'] != '' && $form_config['article_value_one'][$board_info['fn_meta_kw']] != '') $GLOBALS['chg_keywords'] = $form_config['article_value_one'][$board_info['fn_meta_kw']];
							}
						break;
						case 'WRITE' :																								// 쓰기폼
							$form_config['article_value_one'] = array();
							if ($_GET['is_cpy_article'] === 'Y' && $_GET['cp_article_num'] != '') {				// 게시물복사 요청
								$form_config['article_value_one'] = $GLOBALS['lib_common']->get_data($board_info['tbl_name'], $board_info['fld_name_idx'], $_GET['cp_article_num']);
								$form_config['article_value_one']['src_article_num'] = $_GET['cp_article_num'];
							}
							$form_config['form_name'] = board_form($form_config, $board_info, $_GET['prev_url']);
							$form_config['auth_info'] = $GLOBALS['lib_insiter']->get_article_auth($board_info, '', $user_info, 'write', '', $form_config);
							if ($site_page_info['file_name'] == $board_info['write_page']) {
								if ($form_config['auth_info'] !== 'O' && $GLOBALS['auth']['chk_auth_result'] !== 'O') $GLOBALS['lib_common']->alert_url("\'등록\' 권한이 없습니다.", 'E', '', 'document', '', '1', 'Y');
								$is_host = $GLOBALS['lib_insiter']->is_host($user_info['id'], '', $board_info['name']);	// 등록개수제한
								if ($is_host === false && $board_info['input_cnt'] != '') {
									if ($user_info['user_level'] == $GLOBALS['site_config']['visitor_level']) $GLOBALS['lib_common']->alert_url('로그인 후 에 이용 해 주세요.');		// 로그인 후 이용 할 수 있음
									$exp_input_cnt = explode(',', $GLOBALS['lib_insiter']->reserv_replace($board_info['input_cnt'], '', $user_info));			
									$input_cnt_term = $exp_input_cnt['0'];													// 체크할 기간 파악 (T 또는 초 단위)
									if (is_numeric($input_cnt_term)) $input_cnt_term = $input_cnt_term * 60 * 60;
									$input_cnt_array = explode(';', $exp_input_cnt['1']);								// 등록 가능 개수 (날짜와 함께 다수 지정가능)
									if (count($input_cnt_array) > 0) {
										$bias_id = $user_info['id'];															// 개수 파악할 등록자 ID 지정
										$input_cnt_close_date_array = explode(';', $exp_input_cnt['3']);			// 개수별 등록 가능 날짜
										foreach ($input_cnt_array as $key=>$val) {
											$flag_date = $flag_cnt = 'X';
											if (is_numeric($val)) $input_cnt = (int)$val;								// 개수 파악 (숫자인 경우 바로 지정, 문자열인 경우 회원 필드에서 불러와 지정)
											else $input_cnt = (int)$user_info[$val];
											if ($input_cnt > 0) {
												$cnt_my_article = $GLOBALS['lib_insiter']->get_cnt_article($board_info['tbl_name'], ($exp_input_cnt[4] != '' ? $exp_input_cnt[4] : 'sign_date'), $bias_id, $input_cnt_term, 0, $exp_input_cnt['2'], array(), $board_info['fld_name_idx'], array('r_type'=>'I'));
												if ($cnt_my_article[0] < $input_cnt) $flag_cnt = 'O';
											}
											if ($input_cnt_close_date_array[$key] == '' || $user_info[$input_cnt_close_date_array[$key]] >= $GLOBALS['w_time']) $flag_date = 'O';	// 지정날짜가 없거나 미래인 경우 O
											if ($flag_date === 'O' && $flag_cnt === 'O') break;						// 유효날짜이고 등록가능한 개수가 남은 두가지 조건 모두 만족하는 경우 확인 종료
										}
									}
									if ($flag_cnt === 'X') {
										if (!is_array($PV_board_chk_add_cnt[$board_info['name']])) {
											$GLOBALS['lib_common']->alert_url('등록 가능한 개수(' . $cnt_my_article[0] . ')를 초과 하였습니다.');
										} else {
											$GLOBALS['lib_common']->alert_url($PV_board_chk_add_cnt[$board_info['name']][0], 'E', str_replace('{serial_num}', $cnt_my_article[1][0], str_replace('{serial_nums}', '~'.implode('%20', $cnt_my_article[1]), $PV_board_chk_add_cnt[$board_info['name']][1])), 'document', '', '1', 'N', '');
										}
									}
									if ($flag_date === 'X') $GLOBALS['lib_common']->alert_url('등록 가능한 기간' . ($user_info[$input_cnt_close_date_array[$key]] > 0 ? '(' . date('y-m-d H:i:s', $user_info[$input_cnt_close_date_array[$key]]) . ')' : ''). '이 지났습니다.');
								}
							}
						break;
						case 'MODIFY' :	// 수정폼
							$form_config['article_value_one'] = $GLOBALS['lib_insiter']->get_article_one($form_config, $user_info, $board_info);
							$board_info = $GLOBALS['BD_INFO'][$board_info['name']];
							$form_config['auth_info'] = $GLOBALS['lib_insiter']->get_article_auth($board_info, $form_config['article_value_one'], $user_info, 'modify', '', $form_config);
							if ($form_config['auth_info'] !== 'O' && $GLOBALS['auth']['chk_auth_result'] !== 'O') $GLOBALS['lib_common']->alert_url("\'수정\' 권한이 없습니다.", 'E', '', 'document', '', '1', 'Y');
							$form_config['form_name'] = board_form($form_config, $board_info, $_GET['prev_url']);
						break;
						case 'DELETE' :	// 삭제폼
							$form_config['article_value_one'] = $GLOBALS['lib_insiter']->get_article_one($form_config, $user_info, $board_info);
							$board_info = $GLOBALS['BD_INFO'][$board_info['name']];
							$form_config['auth_info'] = $GLOBALS['lib_insiter']->get_article_auth($board_info, $form_config['article_value_one'], $user_info, 'delete', '', $form_config);
							if ($form_config['auth_info'] !== 'O' && $GLOBALS['auth']['chk_auth_result'] !== 'O') $GLOBALS['lib_common']->alert_url("\'삭제\' 권한이 없습니다.", 'E', '', 'document', '', '1', 'Y');
							$form_config['form_name'] = board_form($form_config, $board_info, $_GET['prev_url']);
						break;
						case 'REPLY' :		// 답변폼
							if ($_GET['is_cpy_article'] === 'Y' && $_GET['cp_article_num'] != '') {				// 게시물복사 요청
								$form_config['article_value_one'] = $GLOBALS['lib_common']->get_data($board_info['tbl_name'], $board_info['fld_name_idx'], $_GET['cp_article_num']);
								$form_config['article_value_one'][$board_info['fld_name_idx']] = $_GET['article_num'];
								$form_config['article_value_one']['src_article_num'] = $_GET['cp_article_num'];
							} else {
								$form_config['article_value_one'] = $GLOBALS['lib_insiter']->get_article_one($form_config, $user_info, $board_info);
							}
							$board_info = $GLOBALS['BD_INFO'][$board_info['name']];
							$form_config['auth_info'] = $GLOBALS['lib_insiter']->get_article_auth($board_info, $form_config['article_value_one'], $user_info, 'reply', '', $form_config);
							if ($site_page_info['file_name'] == $board_info['reply_page']) {
								if ($form_config['auth_info'] !== 'O' && $GLOBALS['auth']['chk_auth_result'] !== 'O') $GLOBALS['lib_common']->alert_url("\'답변\' 권한이 없습니다.", 'E', '', 'document', '', '1', 'Y');
								$is_host = $GLOBALS['lib_insiter']->is_host($user_info['id'], '', $board_info['name']);	// 등록개수제한
								if ($is_host === false && $board_info['reply_cnt'] != '') {
									if ($user_info['user_level'] == $GLOBALS['site_config']['visitor_level']) $GLOBALS['lib_common']->alert_url('로그인 후 에 이용 해 주세요.');		// 로그인 후 이용 할 수 있음
									$exp_reply_cnt = explode(',', $GLOBALS['lib_insiter']->reserv_replace($board_info['reply_cnt'], '', $user_info));
									$reply_cnt_term = $exp_reply_cnt['0'];																	// 체크할 기간 파악 (T 또는 초 단위)
									if (is_numeric($reply_cnt_term)) $reply_cnt_term = $reply_cnt_term * 60 * 60;
									$reply_cnt_array = explode(';', $exp_reply_cnt['1']);												// 등록 가능 개수 (날짜와 함께 다수 지정가능)
									if (count($reply_cnt_array) > 0) {
										$bias_id = $user_info['id'];																			// 개수 파악할 등록자 ID 지정
										$reply_cnt_close_date_array = explode(';', $exp_reply_cnt['3']);							// 개수별 등록 가능 날짜
										foreach ($reply_cnt_array as $key=>$val) {
											$flag_date = $flag_cnt = 'X';
											if (is_numeric($val)) $reply_cnt = (int)$val;												// 개수 파악 (숫자인 경우 바로 지정, 문자열인 경우 회원 필드에서 불러와 지정)
											else $reply_cnt = (int)$user_info[$val];
											if ($reply_cnt > 0) {
												$sub_query_array = array("fid='{$form_config['article_value_one']['fid']}' and thread<>''");
												$cnt_my_article = $GLOBALS['lib_insiter']->get_cnt_article($board_info['tbl_name'], 'sign_date', $bias_id, $reply_cnt_term, 0, $exp_reply_cnt['2'], $sub_query_array, $board_info['fld_name_idx']);
												if ($cnt_my_article < $reply_cnt) $flag_cnt = 'O';
											}
											if ($reply_cnt_close_date_array[$key] == '' || $user_info[$reply_cnt_close_date_array[$key]] >= $GLOBALS['w_time']) $flag_date = 'O';		// 지정날짜가 없거나 미래인 경우 O
											if ($flag_date === 'O' && $flag_cnt === 'O') break;										// 유효날짜이고 등록가능한 개수가 남은 두가지 조건 모두 만족하는 경우 확인 종료
										}
									}
									if ($flag_cnt === 'X') $GLOBALS['lib_common']->alert_url('등록 가능한 개수(' . $cnt_my_article . ')를 초과 하였습니다.');
									if ($flag_date === 'X') $GLOBALS['lib_common']->alert_url('등록 가능한 기간' . ($user_info[$reply_cnt_close_date_array[$key]] > 0 ? '(' . date('y-m-d H:i:s', $user_info[$reply_cnt_close_date_array[$key]]) . ')' : ''). '이 지났습니다.');
								}
							}
							$form_config['form_name'] = board_form($form_config, $board_info, $_GET['prev_url']);
						break;
						case 'COMMENT' :																											// 댓글폼
							$form_config['article_value_one'] = $GLOBALS['lib_insiter']->get_article_one($form_config, $user_info, $board_info);
							$form_config['auth_info'] = $GLOBALS['lib_insiter']->get_article_auth($board_info, $form_config['article_value_one'], $user_info, 'comment', '', $form_config);
							$form_config['form_name'] = board_form($form_config, $board_info, $_GET['prev_url']);
						break;
					}
					// 추가 프로세스 (private_info.inc.php 에서 변수정의 하고 해당파일 사용자 구현)
					if (is_array($PV_board_view_process[$board_info['name']])) foreach ($PV_board_view_process[$board_info['name']] as $T_proc_idx=>$T_proc_file_name) if (file_exists($T_proc_file_name)) include $T_proc_file_name;
					if (is_array($PV_board_template_process[$board_info['template']])) foreach ($PV_board_view_process[$board_info['name']] as $T_proc_idx=>$T_proc_file_name) if (file_exists($T_proc_file_name)) include $T_proc_file_name;
				}
			} else {
				$form_config = array('idx'=>$exp['1'], 'page_type'=>'ETC');
				$exp_5 = $GLOBALS['lib_insiter']->explode_sol_div($GLOBALS['DV']['ct5'], $exp['5']);
				$form_config['verify_input'] = $exp_5['0'];
				$form_config['proc_mode'] = $exp_5['1'];
				$form_config['form_property'] = $exp['7'];
				$form_config['form_name'] = etc_form($form_config, $_GET['prev_url']);
			}
			$exp = $GLOBALS['lib_insiter']->reserv_replace($exp, $form_config, $user_info, 'N', 'Y');	 				// 내부변수치환 한 번 더
		}
		$exp['3'] = $T_exp_3;
		if ($exp['3'] != '') {																												// 출력 권한 처리부
			$is_skip = $GLOBALS['lib_insiter']->is_skip($design, $exp['3'], $user_info['user_level'], $i_viewer, $exp['1'], $skip_info);
			if ($is_skip !== 'VIEW') {																										// 볼수 없는 권한 테이블 이면 건너 뜀
				$i_viewer = $is_skip;
				if ($GLOBALS['lib_insiter']->current_table_idx($GLOBALS['TB_IDX']) == $exp['1']) {						// 폼 설정된 테이블이 출력제한에 걸렸다면 폼 해제
					$GLOBALS['FORM_CONFIG']['last'] = $GLOBALS['FORM_CONFIG'][end($GLOBALS['TB_IDX'])];					// 사용완료된 form_config 보관
					$GLOBALS['TB_IDX_L'][] = array_pop($GLOBALS['TB_IDX']);														// 출력완료된 table_idx 제거, 보관
					$form_config = $GLOBALS['FORM_CONFIG'][$GLOBALS['lib_insiter']->current_table_idx($GLOBALS['TB_IDX'])];	// 상위 form_config 복원
					if ($form_config['board_name'] != '') $board_info = $GLOBALS['BD_INFO'][$form_config['board_name']];		// 상위 board_info 복원
					if ($GLOBALS['FORM_TAG'] != '') $GLOBALS['FORM_TAG'] = $GLOBALS['FORM_SCRIPT'][$form_config['form_name']] = '';	// 폼태그제거
				}
				continue;
			}
		}
		if (!isset($form_config['repeat_table_index']) && $exp['9'] === 'REPT' && $form_config['page_type'] === 'LIST')	{			// 반복되는 위치의 시작을 기억해두고 반복될때 마다 레코드를 하나씩 가져온다.
			if ($form_config['total_record'] == 0) {																											// 검색된 레코드가 없는경우, 출력안함.
				$i_viewer = $GLOBALS['lib_fix']->search_first_index($design, $exp['1'], $i_viewer + 1);										// 현재 table 의 끝 다음라인으로 이동 (table 자체를 출력안함)
				continue;
			}
			$GLOBALS['rept_table_depth']++;												// 반복 시작 회수 파악
			$form_config['table_repeat_tag'] = array();								// 반복될 테이블들이 담길 배열
			$form_config['repeat_table_line'] = $i_viewer;
			$form_config['repeat_table_index'] = $exp['1'];
			$form_config['table_repeat_pp'] = $GLOBALS['lib_insiter']->explode_sol_div($GLOBALS['DV']['ct4'], $exp['14']);			// 반복될 테이블 속성
			if ($GLOBALS['rept_table_depth'] > 1) {									// 두번째 이상 반복의 시작인 경우 (중첩반복의 내부)
				$parent_idx = count($GLOBALS['TB_IDX']) - 2;							// 현재 까지의 출력 문자열을 직전 form_config 의 반복 태그 중 현재 진행중인 배열 인덱스에 저장하고 버퍼를 비움(시작~시작 까지의 태그)
				$GLOBALS['FORM_CONFIG'][$GLOBALS['TB_IDX'][$parent_idx]]['table_repeat_tag'][$GLOBALS['FORM_CONFIG'][$GLOBALS['TB_IDX'][$parent_idx]]['repeat_number_rev']-1] = ob_get_clean();
			} else {																				// 첫 반복 시작인 경우
				$GLOBALS['rept_table_depth_rev'] = 0;									// 반복 종료 회수 파악
				$buffer_final .= ob_get_clean();
			}
			ob_start();																			// 테이블 반복은 바로 출력하지 않고 버퍼에서 배열변수로 모두 담아낸 후 일괄 출력함.
		}
		if ($exp['13'] != '') {																// 테이블레이아웃이 지정된경우
			$skin_info = $GLOBALS['lib_insiter']->explode_sol_div($GLOBALS['DV']['ct4'], $exp['13']);
			$img_dir = "{$DIRS['include_root']}skin/table/{$skin_info['0']}/";
			preg_match("|({$GLOBALS[site_config][replace_str_open_close][0]}[^{$GLOBALS[site_config][replace_str_open_close][1]}]*{$GLOBALS[site_config][replace_str_open_close][1]})|", $skin_info['1'], $regs_VNQ);
			$var_name_query = str_replace($GLOBALS['site_config']['replace_str_open_close'], '', strtolower($regs_VNQ['0']));
			$skin_info['1'] = str_replace($regs_VNQ['0'], $user_info[$var_name_query], $skin_info['1']);
			$skin_file_contents = $GLOBALS['lib_insiter']->get_skin_file("{$img_dir}box.html", array('style'=>$skin_info['0'], 'img_dir'=>$img_dir, 'title'=>$skin_info['1'], 'padding'=>$skin_info['2'], 'css_file'=>$skin_info['3']));
			if ($skin_file_contents != '') {												// 지정한 레이아웃이 있는 경우
				$skin_file_contents = explode("{$GLOBALS[site_config][replace_str_open_close][0]}CONTENTS{$GLOBALS[site_config][replace_str_open_close][1]}", $skin_file_contents);	// {CONTENTS} 를 기준으로 나누어
				print_method($skin_file_contents['0'], $save_var_tag_idx);		// 앞부분은 출력
				$T_val_name = "TS_{$exp['1']}_close";									// 뒷부분은 나중에 출력할 수 있도록 담아둔다.
				$$T_val_name = $skin_file_contents['1'];
			}
		}
		if ($exp['12'] != '') $tag_both[$exp['1']] = $GLOBALS['lib_insiter']->explode_sol_div($GLOBALS['DV']['ct4'], $exp['12']);		// 시작태그, 끝 태그 출력
		else $tag_both[$exp['1']] = array();

		if ($GLOBALS['FORM_TAG'] != '') {												// 폼태그출력 (위치 중요)
			print_method($GLOBALS['FORM_TAG'], $save_var_tag_idx);
			$GLOBALS['FORM_TAG'] = '';
		}
		if ($exp['2'] != '') {
			$exp['2'] = ' ' . $exp['2'];													// 속성출력
			if (stripos($exp['2'], 'unveil') !== false) {							// 속성에 unveil이 있는 div 만 버퍼에 따로 담음
				$GLOBALS['unveil'] = $exp[1];
				$buffer_final .= ob_get_clean();
				ob_start();
			}
		}
		if ($exp['0'] === 'TBLO') {
			print_method("{$tag_both[$exp[1]][0]}<table{$exp['2']}>{$tag_both[$exp[1]][2]}", $save_var_tag_idx);
		} else {
			$tag_both[$exp[1]][0] = str_replace('%lpa%', "lpa-{$form_config['div']}", $tag_both[$exp[1]][0]);
			if (trim($exp['2']) !== 'DELETE') {
				print_method("{$tag_both[$exp[1]][0]}<div{$exp['2']}>{$tag_both[$exp[1]][2]}", $save_var_tag_idx);
			} else {
				print_method("{$tag_both[$exp[1]][0]}{$tag_both[$exp[1]][2]}", $save_var_tag_idx);
				$delete_tags[] = $exp[1];
			}			
		}

		if ($form_config['idx'] != '') {
			$form_config['sub_query'] = $GLOBALS['form_sub_query'];				// 서브쿼리 저장
			$GLOBALS['FORM_CONFIG'][$form_config['idx']] = $form_config;		// 전역변수 갱신
		}
		
		$TAB_IDX++;
		continue;
	} else if ($exp['0'] === 'TBLC' || $exp['0'] === 'DIVC') {
		if ($exp['0'] === 'TBLC') {
			print_method("{$tag_both[$exp[1]][3]}</table>{$tag_both[$exp[1]][1]}", $save_var_tag_idx);
		} else {
			if (!in_array($exp[1], $delete_tags)) print_method("{$tag_both[$exp[1]][3]}</div>{$tag_both[$exp[1]][1]}", $save_var_tag_idx);
			else print_method("{$tag_both[$exp[1]][3]}{$tag_both[$exp[1]][1]}", $save_var_tag_idx);
		}
		if ($form_config['idx'] == $exp['1']) {										// 게시판설정을 시작한 테이블의 끝
			if ($form_config['skip_form'] !== 'Y') {
				$etc_tag = '';
				if ($GLOBALS['FLAG']['JOIN_AGREE'] === 'Y') $etc_tag = "<input type=\"hidden\" name=\"T_insiter_join_agree\" value=\"\" id=\"T_insiter_join_agree_{$form_config['form_name']}\" />";
				print_method("{$etc_tag}</form>\n", $save_var_tag_idx);
			}
			$GLOBALS['FORM_CONFIG']['last'] = $GLOBALS['FORM_CONFIG'][end($GLOBALS['TB_IDX'])];								// 사용완료된 form_config 보관
			$GLOBALS['TB_IDX_L'][] = array_pop($GLOBALS['TB_IDX']);																	// 출력완료된 table_idx 제거, 보관
			$form_config = $GLOBALS['FORM_CONFIG'][$GLOBALS['lib_insiter']->current_table_idx($GLOBALS['TB_IDX'])];	// 상위 form_config 복원
			if ($form_config['board_name'] != '') $board_info = $GLOBALS['BD_INFO'][$form_config['board_name']];		// 상위 board_info 복원
		}

		$T_val_name = "TS_{$exp['1']}_close";
		if ($$T_val_name != '') {							// 레이아웃이 적용된 테이블인경우 해당 레이아웃의 나머지 부분을 출력함.
			print_method($$T_val_name, $save_var_tag_idx);
			unset($$T_val_name);
		}

		if ($form_config['repeat_table_index'] == $exp['1']) {																		// 테이블반복이 설정된 경우
			if ($_GET['SCROLLLOADING'] !== 'Y' && $form_config['tpl'] > 0 && $form_config['repeat_number_rev'] == $form_config['tpl']) $form_config['repeat_number'] = 1;
			if ($GLOBALS['rept_table_depth_rev'] == 0) $form_config['table_repeat_tag'][] = trim(ob_get_clean());		// 첫 반복 끝(마지막depth) 인 경우 반복되는 테이블을 1개씩 배열에 저장
			else $form_config['table_repeat_tag'][$form_config['repeat_number_rev']-1] .= trim(ob_get_clean());		// 처음 또는 중간 depth 인경우
			ob_start();																																// 출력버퍼링 재 시작
			if ($form_config['repeat_number'] > 1) {																						// 마지막 반복인지 확인
				$form_config['repeat_number']--;																								// 반복회수 차감
				$form_config['repeat_number_rev']++;																						// 반복회수 증가
				$form_config['repeat_number_ttt']++;
				$i_viewer = $form_config['repeat_table_line'] - 1;																		// 현재 인덱스를 테이블 반복 시작 인덱스로 바꿈.
				if ($form_config['total_record'] > 0) {																					// 레코드값 추출 및 댓글, 연결글 정보 추출
					$T_article_value = get_article_value($form_config, $board_info, $user_info);
					$GLOBALS['AT_INFO_ONE']["{$T_article_value[board_name]}{$T_article_value[serial_num]}"] = $form_config['article_value_one'] = $T_article_value;
				}
			} else if ($form_config['repeat_number'] <= 1) {	// 반복끝 (원래 '== 1' 조건을 오버된 페이지 값이 넘어오는 경우 rept_table_depth 값이 -가 안 되어 마크업이 깨지는 오류가 나서 '<= 1' 조건으로 변경함, 2017-01-09)
				$table_info = array('lpa'=>$form_config['div'], 'pp_table'=>$form_config['table_repeat_pp']['0'], 'pp_tr'=>$form_config['table_repeat_pp']['1'], 'pp_td'=>$form_config['table_repeat_pp']['2'], 'div_tr'=>$form_config['table_repeat_pp']['3'], 'div_td'=>$form_config['table_repeat_pp']['4'], 'pp_group'=>$form_config['table_repeat_pp']['17'], 'pp_tag_open'=>$form_config['table_repeat_pp']['18'], 'pp_tag_close'=>$form_config['table_repeat_pp']['19']);
				if ($form_config['table_repeat_pp']['7'] == '') {
					if ($exp['0'] == 'TBLC') {
						$form_config['table_repeat_tag'] = $GLOBALS['lib_common']->get_html_table($form_config['table_repeat_tag'], $table_info, $form_config['table_repeat_pp']['5']);
					} else {
						$form_config['table_repeat_tag'] = implode('', $form_config['table_repeat_tag']);
					}
				} else {
					$scroll_info = array('name'=>'ST_' . substr($form_config['idx'], 6), 'direction'=>$form_config['table_repeat_pp']['7']/*, 'width'=>$form_config['table_repeat_pp']['8']*/, 'div_height'=>$form_config['table_repeat_pp']['9'], 'cnt'=>$form_config['table_repeat_pp']['10'], 'view_cnt'=>$form_config['table_repeat_pp']['11'], 'ul_ppt'=>$form_config['table_repeat_pp']['12'], 'li_ppt'=>$form_config['table_repeat_pp']['13'], 'auto'=>$form_config['table_repeat_pp']['14'], 'speed'=>$form_config['table_repeat_pp']['15'], 'wait'=>$form_config['table_repeat_pp']['16']);
					$form_config['table_repeat_tag'] = $GLOBALS['lib_common']->get_html_table_scroll($form_config['table_repeat_tag'], $table_info, $scroll_info);
				}
				$GLOBALS['rept_table_depth']--;
				$GLOBALS['rept_table_depth_rev']++;
				if ($GLOBALS['rept_table_depth'] > 0) {
					if ($form_config['repeat_number'] > 1) {
						$parent_idx = count($GLOBALS['TB_IDX']) - 2;
						$GLOBALS['FORM_CONFIG'][$GLOBALS['TB_IDX'][$parent_idx]]['table_repeat_tag'][$GLOBALS['FORM_CONFIG'][$GLOBALS['TB_IDX'][$parent_idx]]['repeat_number_rev']-1] .= $form_config['table_repeat_tag'];
					} else {
						print_method($form_config['table_repeat_tag'], $save_var_tag_idx);
					}
					$exp_rept = $GLOBALS['lib_insiter']->explode_sol_div($GLOBALS['DV']['dv'], $design[$form_config['repeat_table_line']]);
					$exp_rept[9] = 'REPT';
					$design[$form_config['repeat_table_line']] = $GLOBALS['lib_insiter']->implode_sol_div($GLOBALS['DV']['dv'], $exp_rept);	// 내부 반복은 상위 반복에서 다시 사용될 수 있으므로 REPT 복원
				} else if ($GLOBALS['rept_table_depth'] == 0) {
					print_method($form_config['table_repeat_tag'], $save_var_tag_idx);
				}
				unset($form_config['repeat_table_index'], $form_config['repeat_table_line'], $form_config['table_repeat_tag']);
			}
			if ($form_config['idx'] != '') $GLOBALS['FORM_CONFIG'][$form_config['idx']] = $form_config;					// 전역변수 갱신
		}
		if ($exp[1] === $save_var_tag_idx[1]) $save_var_tag_idx = array();
		if ($exp[1] === $GLOBALS['unveil']) {
			$GLOBALS['unveil'] = 'N';
			$buffer_unveil = preg_replace('/(<img [^>]*)(src=)([\'"]*)([^\'"]+)([\'"]*)([^>]*>)/i', '${1}src="/images/water_mark.gif" data-src="$4"$6', ob_get_clean());
			$buffer_final .= $buffer_unveil;
			ob_start();
		}
		//continue;
	} else if ($exp['0'] === 'TRO') {
		if ($exp['3'] != '') {																													// 출력 권한 처리부
			$is_skip = $GLOBALS['lib_insiter']->is_skip($design, $exp['3'], $user_info['user_level'], $i_viewer, $exp['1'], $skip_info);
			if ($is_skip !== 'VIEW') {																											// 볼수 없는 권한 테이블 이면 건너 뜀
				$i_viewer = $is_skip;
				continue;
			}
		}
		$tr_property = '';
		$exp = $GLOBALS['lib_insiter']->reserv_replace($exp, $form_config, $user_info);												// 내부변수치환
		if (!isset($form_config['repeat_tr_index']) && $exp['9'] === 'REPT' && $form_config['page_type'] === 'LIST')	{	// 반복되는 위치의 시작을 기억해두고 반복될때 마다 레코드를 하나씩 가져온다.
			$GLOBALS['rept_tr_depth']++;
			if ($form_config['total_record'] == 0) {																								// 현재 tr 의 끝 다음라인으로 이동 (줄 자체를 출력안함)
				$i_viewer = $GLOBALS['lib_fix']->search_first_index($design, $exp['1'], $i_viewer + 1);
				continue;
			}
			$form_config['repeat_tr_line'] = $i_viewer;																							// 반복할 line
			$form_config['repeat_tr_index'] = $exp['1'];																							// 반복할 tr index=...
			$form_config['repeat_tr_rev'] = 0;																										// 현재까지 반복회수
			/*$exp['9'] = '';
			$design[$i_viewer] = $GLOBALS['lib_insiter']->implode_sol_div($GLOBALS['DV']['dv'], $exp);						// 반복될 경우를 대비해서 'REPT' 문자열을 삭제한다.*/
			if ($exp['11'] != '') {																														// 반복 사이줄 속성 및 사이줄 출력주기
				$ext_div_tr = $GLOBALS['lib_insiter']->explode_sol_div($GLOBALS['DV']['ct4'], $exp['11']);
				$form_config['repeat_div_tr'] = $ext_div_tr['0'];
				if ($ext_div_tr['1'] == '') $ext_div_tr['1'] = 1;
				$form_config['repeat_div_tr_term'] = $ext_div_tr['1'];
			}
			if ($form_config['idx'] != '') $GLOBALS['FORM_CONFIG'][$form_config['idx']] = $form_config;							// 전역변수 갱신
		}
		if ($exp['10'] == '') {
			$tr_property .= $exp['2'];
		} else {
			if (($form_config['abs_repeat'] - $form_config['repeat_number']) % 2 == 0) $tr_property .= $exp['2'];
			else $tr_property .= $exp['10'];
		}
		if ($tr_property != '') $tr_property = ' ' . trim($tr_property);
		if ($exp['12'] != '') $tag_both[$exp['1']] = $GLOBALS['lib_insiter']->explode_sol_div($GLOBALS['DV']['ct4'], $exp['12']);
		else $tag_both[$exp['1']] = array();
		print_method("{$tag_both[$exp[1]][0]}<tr{$tr_property}>{$tag_both[$exp[1]][2]}", $save_var_tag_idx);
		$TAB_IDX++;
		//continue;
	} else if ($exp['0'] === 'TRC') {
		if ($form_config['repeat_tr_index'] == $exp['1']) {																					// 줄반복이 설정된 경우
			if ($_GET['SCROLLLOADING'] !== 'Y' && $form_config['tpl'] > 0 && $form_config['repeat_tr_rev'] == $form_config['tpl'] - 1) $form_config['repeat_number'] = 1;
			if ($form_config['repeat_number'] > 1) {
				$form_config['repeat_number']--;																										// 잔여 반복회수 차감
				$form_config['repeat_tr_rev']++;																										// 현재회수 증가
				$i_viewer = $form_config['repeat_tr_line'] - 1;																					// 현재 인덱스를 테이블 반복 시작 인덱스로 바꿈.
				if ($form_config['total_record'] > 0) {																							// 레코드값 추출 및 댓글, 연결글 정보 추출
					$T_article_value = get_article_value($form_config, $board_info, $user_info);
					$GLOBALS['AT_INFO_ONE']["{$T_article_value['board_name']}{$T_article_value['serial_num']}"] = $form_config['article_value_one'] = $T_article_value;
				}
				if ($form_config['repeat_div_tr_term'] != '' && ($form_config['abs_repeat'] - $form_config['repeat_number']) % $form_config['repeat_div_tr_term'] == 0) print_method($form_config['repeat_div_tr'], $save_var_tag_idx);	// 출력 주기에 따라 사이줄 출력
			} else {
				unset($form_config['repeat_tr_index'], $form_config['repeat_div_tr'], $form_config['repeat_div_tr_term']);
				$GLOBALS['rept_tr_depth']--;
			}
			if ($form_config['idx'] != '') $GLOBALS['FORM_CONFIG'][$form_config['idx']] = $form_config;						// 전역변수 갱신
		}
		print_method("{$tag_both[$exp[1]][3]}</tr>{$tag_both[$exp[1]][1]}", $save_var_tag_idx);
		//continue;
	} else if ($exp['0'] === 'TDO' || $exp['0'] === 'THO') {
		if ($form_config['pass_td_index'][$exp['1']] > 0) {																				// rowspan 처리
			$form_config['pass_td_index'][$exp['1']]--;
			$exp['3'] = '99:U::T::T::T::T:::::::1=2:A:A';
		}
		if ($exp['3'] != '') {																														// 출력 권한 처리부
			$is_skip = $GLOBALS['lib_insiter']->is_skip($design, $exp['3'], $user_info['user_level'], $i_viewer, $exp['1'], $skip_info);
			if ($is_skip !== 'VIEW') {																												// 볼수 없는 권한 테이블 이면 건너 뜀
				$i_viewer = $is_skip;
				continue;
			}
		}
		$exp = $GLOBALS['lib_insiter']->reserv_replace($exp, $form_config, $user_info);											// 내부변수치환
		if ($exp['2'] != '') $exp['2'] = ' ' . trim($exp['2']);
		if ($exp['12'] != '') $tag_both[$exp['1']] = $GLOBALS['lib_insiter']->explode_sol_div($GLOBALS['DV']['ct4'], $exp['12']);
		else $tag_both[$exp['1']] = array();
		if ($exp['0'] === 'TDO') print_method("{$tag_both[$exp[1]][0]}<td{$exp['2']}>{$tag_both[$exp[1]][2]}{$td_nl}", $save_var_tag_idx);
		else print_method("{$tag_both[$exp[1]][0]}<th{$exp['2']}>{$tag_both[$exp[1]][2]}{$td_nl}", $save_var_tag_idx);
		if (preg_match("/rowspan=['\"]*([0-9]+)['\"]*/", $exp['2'], $matches) && $form_config['repeat_number'] > 1) {	// rowspan 처리
			$rowspan = $matches[1];
			$form_config['pass_td_index'][$exp['1']] = $rowspan - 1;
			//if ($form_config['idx'] != '') $GLOBALS['FORM_CONFIG'][$form_config['idx']] = $form_config;					// 전역변수 갱신 (필요한 경우 주석 풀어야 할 수 있음)
		}
		$TAB_IDX++;
		//continue;
	} else if ($exp['0'] === 'TDC' || $exp['0'] === 'THC') {
		if ($exp['0'] === 'TDC') print_method("{$tag_both[$exp[1]][3]}</td>{$tag_both[$exp[1]][1]}", $save_var_tag_idx);
		else print_method("{$tag_both[$exp[1]][3]}</th>{$tag_both[$exp[1]][1]}", $save_var_tag_idx);
		//continue;
	} else if ($exp['0'] === 'IMG') {
		$tag = $GLOBALS['lib_insiter']->input_image($root, $exp['3'], $exp['4'], $exp['5']);
		$component_view = $GLOBALS['lib_insiter']->insert_blank($tag, $exp['12'], $exp['13']);
		$component_view = $GLOBALS['lib_insiter']->reserv_replace($component_view, $form_config, $user_info, 'N', 'N');	// 내부변수치환
		print_method($component_view, $save_var_tag_idx);
		//continue;
	} else if ($exp['0'] === 'IFRM') {
		$iframe_src = $exp['1'];
		$iframe_property = $GLOBALS['lib_insiter']->explode_sol_div($GLOBALS['DV']['ct4'], $exp['2']);
		$tag = "<iframe src='$iframe_src' width='{$iframe_property['0']}' height='{$iframe_property['1']}' name='{$iframe_property['2']}' frameborder='{$iframe_property['3']}' hspace='{$iframe_property['4']}' vspace='{$iframe_property['5']}' marginwidth='{$iframe_property['7']}' marginheight='{$iframe_property['8']}' {$iframe_property['6']}></iframe>";
		$component_view = $GLOBALS['lib_insiter']->reserv_replace($tag, $form_config, $user_info);
		$component_view = $GLOBALS['lib_insiter']->insert_blank($component_view, $exp['12'], $exp['13']);
		print_method($component_view, $save_var_tag_idx);
		//continue;
	} else if ($exp['0'] === 'FLASH') {
		$exp = $GLOBALS['lib_insiter']->reserv_replace($exp, $form_config, $user_info);
		$flash_src = $exp['1'];
		$flash_property = $GLOBALS['lib_insiter']->explode_sol_div($GLOBALS['DV']['ct4'], $exp['2']);
		$flash_replace_text = $exp['3'];
		include 'flash.inc.php';
		$component_view = $GLOBALS['lib_insiter']->insert_blank($tag, $exp['12'], $exp['13']);
		print_method($component_view, $save_var_tag_idx);
		//continue;
	} else if ($exp['0'] === 'MEDIA') {
		$saved_text = str_replace(chr(92).r.chr(92).n, "\n", $exp['1']);		// 업데이트/검증 후 제거할 부분
		$saved_text = str_replace(chr(92).n, "\n", $saved_text);
		$saved_text = $GLOBALS['lib_insiter']->replace_include_string($saved_text);
		$component_view = str_replace("{$GLOBALS[site_config][replace_str_open_close][0]}레이어{$GLOBALS[site_config][replace_str_open_close][1]}", '', $saved_text);
		$component_view = $GLOBALS['lib_insiter']->insert_blank($component_view, $exp['12'], $exp['13']);
		$component_view = $GLOBALS['lib_insiter']->reserv_replace($component_view, $form_config, $user_info, 'N', 'N');	// 내부변수치환
		print_method($component_view, $save_var_tag_idx);
		/*$exp = $GLOBALS['lib_insiter']->reserv_replace($exp, $form_config, $user_info);
		$media_src = $exp['1'];
		$media_property = $GLOBALS['lib_insiter']->explode_sol_div($GLOBALS['DV']['ct4'], $exp['2']);
		include 'media.inc.php';
		$component_view = $GLOBALS['lib_insiter']->insert_blank($tag, $exp['12'], $exp['13']);
		print_method($component_view, $save_var_tag_idx);
		//continue;*/
	} else if ($exp['0'] === 'ARTBX') {
		/* 게시판 입력 도구부분(ARTBX|name|checkbox|size=15 maxlength=30|width=200 height=300||||||0|0|0|0||)
			_rpr_="Y" 는 값이 없는 치환문자열을 제거 하는 마크업으로 첫번째 reserv_replace() 호출에서 처리 됨
			다만 치환문자열에 ARTBXAfter 이 표시된 경우 첫번째 reserv_replace() 호출은 건너 뛰게 되므로 치환 되지 않는다.
			다음 _rpp_="Y" 인 경우 입력상자의 저장값에 사용된 치환문자열이 치환되지 않도록 하는데 다만 치환문자열에 ARTBXAfter 이 표시된 경우 _rpp_="Y" 는 무시 된다.
			(_rpp_="Y" 가 필요한 경우 : 페이지편집->입력상자속성->기본내용입력->사용자값->입력상자의 치환문자열에 넣은 내용이 치환되지 않고 그대로 나와야 할 때만 사용함!)
			(ARTBXAfter 가 필요한 경우 : 입력상자 태그가 생성된 후에 치환문자열이 실행되어야 할 때, 구분자가 먼저 치환되면 태그 생성시 문제가 될 수 있으므로, 시스템소스편집 상자와 인클루드 입력폼의 파일명 입력상자에서 사용됨)
			한계 : 첫번째 치환은 무시(ARTBXAfter표시)하고 두번째 치환만 되게 하는데 두번째 치환에서 저장값 치환되지 않도록(_rpp_="Y" 표시) 적용하는 것은 불가능 함, 이와 같은 경우의 수가 현재까진 불필요한 상황이므로 일단 여기 까지만 ㅠㅠ
			위 한계의 현상 : 값이 있는 경우에도 치환되지 않도록 할 방법이 없음 (이와 같은 경우의 수가 필요할 경우 다시 로직 개선)  */
		$keep_rep_str = 'Y';										// 값 없는 치환문자열 유지
		if ($exp['2'] === 'file_ifrm') $keep_rep_str = 'N';
		if (stripos($exp[3], '_rpr_="Y"') !== false) {	// 마크업 확인
			$exp[3] = str_ireplace('_rpr_="Y"', '', $exp[3]);
			$keep_rep_str = 'N';
		}
		if (strpos($design[$i_viewer], 'ARTBXAfter') === false) $exp = $GLOBALS['lib_insiter']->reserv_replace($exp, $form_config, $user_info, 'N', $keep_rep_str, 'N', 'ARTBXBefore');
		$exp = str_replace("{$GLOBALS['site_config']['replace_str_open_close'][0]}LIVE_CODE{$GLOBALS['site_config']['replace_str_open_close'][1]}", $_SESSION['live_code'], $exp);	// 입력상자 만들 때 지정하는 것으로, 유지함
		$replace_pass = 'N';										// 치환문자열 무시
		if (stripos($exp[3], '_rpp_="Y"') !== false && strpos($design[$i_viewer], 'ARTBXAfter') === false) {	// 마크업 확인
			$exp[3] = str_ireplace('_rpp_="Y"', '', $exp[3]);
			$replace_pass = 'Y';
		}
		$tag = make_board_input_box($board_info, $form_config, $user_info, $exp, $site_page_info, $replace_pass);
		$component_view = $GLOBALS['lib_insiter']->insert_blank($tag, $exp['12'], $exp['13']);
		$component_view = $GLOBALS['lib_insiter']->reserv_replace($component_view, $form_config, $user_info, 'N', $keep_rep_str, 'N', 'ARTBXAfter');		// 내부변수치환
		if ($replace_pass === 'Y') $component_view = str_replace('_____M____2_____', '}', $component_view);
		if ($save_var_tag_idx[0] == '' && $exp['15'] != '') $save_var_tag_idx[0] = $exp['15'];
		print_method($component_view, $save_var_tag_idx);
		//continue;
	} else if ($exp['0'] === 'BDEXT') {
		if ($exp['1'] != '') $include_file = "{$DIRS['board_root']}calendar/{$exp['1']}";
		else $include_file = "{$DIRS['board_root']}tree/{$exp['2']}";
		include $include_file;
		//continue;
	} else if ($exp['0'] === 'LGNBX') {		
		// 로그인 입력상자
		$exp = $GLOBALS['lib_insiter']->reserv_replace($exp, $form_config, $user_info, 'N', 'N', 'N', 'Y');	// 내부변수치환
		$tag = make_login_box($exp);
		$component_view = $GLOBALS['lib_insiter']->insert_blank($tag, $exp['12'], $exp['13']);
		print_method($component_view, $save_var_tag_idx);
		//continue;
	} else if ($exp['0'] === 'CONTO') {
		// 레이아웃외부콘텐츠(레이아웃에 콘텐츠를 넣고자 할때)
		if ($site_page_info['tag_contents_out'] != '') {
			if (substr($site_page_info['tag_contents_out'], 0, 7) === 'include') {
				include trim(substr($site_page_info['tag_contents_out'], 7));
			} else {
				print_method($site_page_info['tag_contents_out'], $save_var_tag_idx);
			}
		} else {
			//print_method('페이지의 콘텐츠 외부값이 없습니다.', $save_var_tag_idx);
		}
		//continue;
	} else if ($exp['0'] === 'INCD') {
		// 인클루드 변수를 설정한다.
		$exp = $GLOBALS['lib_insiter']->reserv_replace($exp, $form_config, $user_info);						// 내부변수치환
		$inc_exp = $GLOBALS['lib_insiter']->explode_sol_div($GLOBALS['DV']['ct2'], $exp['1']);
		if ($inc_exp['1'] != '') {
			$var_include = $GLOBALS['lib_common']->parse_property($inc_exp['1'], ',', '=', '', 'N');
			foreach ($var_include as $key_inc=>$value_inc) {
				$$key_inc = $GLOBALS['lib_insiter']->replace_include_string($value_inc);						// 변수대입 및 구분자 복원
			}
		}
		$T_first_str = substr($inc_exp['0'], 0, 1);
		if ($T_first_str !== '.' && $T_first_str !== '/') $inc_file_head = $root;
		else $inc_file_head = '';
		if ($inc_exp['2'] == '') include $inc_file_head . $inc_exp['0'];
		else include_once $inc_file_head . $inc_exp['0'];
		//continue;
	} else if ($exp['0'] === 'IMPT') {																						// 게시물 항목 등의 치환문자열 적용을 위해 임포트 명령어 처리를 유지
		$exp = $GLOBALS['lib_insiter']->reserv_replace($exp, $form_config, $user_info, 'N', 'Y');
		if ($design_file == $exp[1]) die("자신의 페이지는 임포트 할 수 없습니다.");										// 교착상태방지
		if (array_search($exp[1], $import_files)) die("교차 임포트는 허용되지 않습니다.");
		if ($exp['2'] != '') {
			$var_import = $GLOBALS['lib_common']->parse_property($exp['2'], ',', '=', '', 'N');
			foreach ($var_import as $key_inc=>$value_impt) {
				$$key_inc = $GLOBALS['lib_insiter']->replace_include_string($value_impt);						// 변수대입 및 구분자 복원
			}
		}
		$site_import_page_info = $GLOBALS['lib_fix']->get_site_page_info($exp[1]);
		print_method($GLOBALS['lib_insiter']->reserv_replace($site_import_page_info['tag_header'], $form_config, $user_info), $save_var_tag_idx);
		print_method($GLOBALS['lib_insiter']->reserv_replace($site_import_page_info['tag_body_in'], $form_config, $user_info), $save_var_tag_idx);
		$tag_body_out_import .= $site_import_page_info['tag_body_out'];
		$import_design = str_replace("{$GLOBALS[DV][dv]}index=", "{$GLOBALS[DV][dv]}index=ID{$import_files_size}", $GLOBALS['lib_fix']->design_load($DIRS, $exp[1], $site_import_page_info));	// 같은 인덱스 회피
		$design[$i_viewer] = '';
		array_splice($design, $i_viewer+1, 0, $import_design);														// 임포트 파일 내용 삽입
		$cnt_viewer = count($design);
		$import_design_size = count($import_design);
		if ($import_files_size > 0) {																							// 상위 임포트 라인이 있으면 현재 임포트파일의 줄 수를 각 라인에 더한다.
			for ($ifsi=0; $ifsi<$import_files_size; $ifsi++) $import_lines[$ifsi] += $import_design_size;
		}
		$new_start_line = $i_viewer + $import_design_size;																// 임포트 파일 다음라인(현재파일 시작점 저장)
		array_push($import_files, $exp[1]);
		array_push($import_lines, $new_start_line);
		$import_files_size = count($import_files);																		// 임포트된 회수를 기록
		//continue;
	} else if ($exp['0'] === 'NAVI') {
		if ($exp['1'] != '') {
			$navi_info = array();
			$var_command = $GLOBALS['lib_common']->parse_property($exp['1'], ',', '=', '', 'N');			
			foreach ($var_command as $key_inc=>$value_inc) {
				$key_inc = trim($key_inc);
				$navi_info[$key_inc] = $value_inc;																			// 변수대입 및 구분자 복원
				//$navi_info[$key_inc] = $GLOBALS['lib_insiter']->replace_include_string($value_inc);		// 변수대입 및 구분자 복원
			}
		}
		if ($_GET['category_id'] != '' || $_GET['goods_serial'] != '') $tag = get_navigation($navigation, $navi_info);		// 쇼핑몰 전용
		else $tag = $GLOBALS['lib_insiter']->make_navigation($site_page_info, $navi_info/*, $link_method, $navi_home_file*/);
		$tag = $GLOBALS['lib_insiter']->reserv_replace($tag, $form_config, $user_info, 'N', 'N', 'N');
		$component_view = $GLOBALS['lib_insiter']->insert_blank($tag, $exp['12'], $exp['13']);
		print_method($component_view, $save_var_tag_idx);
		//continue;
	} else if ($exp['0'] === 'VARPRINT') {																									// 변수출력
		$T_exp_var_name = explode('=', $exp['1']);
		$T_print_var = $GLOBALS['lib_insiter']->reserv_replace($T_exp_var_name['1'], $form_config, $user_info, 'N', 'N', 'N');
		$component_view = $GLOBALS['lib_insiter']->insert_blank($T_print_var, $exp['12'], $exp['13']);
		print_method($component_view, $save_var_tag_idx);
		//print($component_view);
		//continue;
	} else {
		if ($exp['1'] != '') {
			$var_command = $GLOBALS['lib_common']->parse_property($exp['1'], ',', '=', '', 'N');
			foreach ($var_command as $key_inc=>$value_inc) {
				$key_inc = trim($key_inc);
				$$key_inc = $value_inc;																			// 변수대입 및 구분자 복원
				//$$key_inc = $GLOBALS['lib_insiter']->replace_include_string($value_inc);		// 변수대입 및 구분자 복원
			}
		}
		include $DIRS['include_root'] . 'exec_user_command.inc.php';
		$tag = $exec_user_tag;
		$component_view = $GLOBALS['lib_insiter']->insert_blank($tag, $exp['12'], $exp['13']);
		print_method($component_view, $save_var_tag_idx);
	}
	
	if (($i_viewer >= $import_lines[$import_files_size-1]) && ($import_files_size > 0)) {
		array_pop($import_files);
		array_pop($import_lines);
		$import_files_size = count($import_files);	// 임포트된 회수를 기록
	}
	
	if ($speed_print === 'Y') {
		$T_time_end = $GLOBALS['lib_common']->get_microtime();																				// 시작시간 기록
		$T_time = $T_time_end - $T_time_start;
		$T_time_total += $T_time;
		$time_check[] = number_format($T_time, 10) . ', ' . number_format($T_time_total, 10) . " : {$design[$i_viewer]}";
	}
}

if ($speed_print === 'Y') {
	echo('
<!--
' . implode("\n", $time_check) . '
//-->
');
}

if (($_GET['AJAX'] == '' && $skin_file != '') || $site_page_info['file_name'] === $GLOBALS['site_config']['homepage']) {
	include "{$DIRS['include_root']}popup_open.inc.php";
	if ($user_info['serial_num'] != '') include "{$DIRS['board_root']}paper/paper.inc.php";
	if ($GLOBALS['site_config']['ajax_link'] != '') {
		if ($site_page_info['type'] !== 'UM') echo("<script type=\"text/javascript\" src=\"/include/jquery/jquery-history.js\" charset=\"{$GLOBALS['site_config']['char_set']}\"></script>");
	}
	if ($speed_print === 'Y') {
		if ($user_info['user_level'] == $GLOBALS['site_config']['admin_level']) {
			$excute_time = $GLOBALS['lib_common']->get_microtime() - $GLOBALS['w_microtime'];
			echo("<!-- 총 실행시간 : $excute_time //-->");
		}
	}
	//echo("<script id=\"AB_dynamic_script\" type=\"text/javascript\"></script>");
}

foreach ($PV_view_after_include_list as $T_proc_idx=>$T_proc_file_name) if (file_exists($T_proc_file_name)) include_once $T_proc_file_name;

echo(str_replace('</body>', '<img style="display:none;position:absolute;left:50%;top:50%;z-index:100001" id="loading_img" src="/cooker/theme/' . $GLOBALS['site_config']['admin_thema'] . '/images/loading_front.gif" alt="loading..." /></body>', $GLOBALS['lib_insiter']->reserv_replace($GLOBALS['body_out_tag'], '', $user_info)));

$buffer_final .= ob_get_clean();
$buffer_final = real_time_chg_html($site_page_info, $buffer_final, $speed_print);	// 타이틀, 메타 태그 반영, 중간의 style, script 태그들을 배열로 모음
if (count($GLOBALS['buffer_style_file']) > 0) {	// 중간의 css 파일을 모아 </head> 바로 앞으로 이동
	if (strpos($buffer_final, '</head>') !== false) $buffer_final = str_replace('</head>', implode("\n", $GLOBALS['buffer_style_file']) . '</head>', $buffer_final);
	else $buffer_final = implode("\n", $GLOBALS['buffer_style_file']) . $buffer_final;
}
if (count($GLOBALS['buffer_style']) > 0) {		// 중간의 <style>을 모아 </head> 바로 앞으로 이동
	if (strpos($buffer_final, '</head>') !== false) $buffer_final = str_replace('</head>', '<style>' . implode("\n", $GLOBALS['buffer_style']) . '</style></head>', $buffer_final);
	else $buffer_final = '<style>' . implode("\n", $GLOBALS['buffer_style']) . '</style>' . $buffer_final;
}
	
if ($_GET['DLG'] === 'Y' && ($user_info['id'] === $GLOBALS['site_config']['s_admin'] || strpos($user_info['admin_level'], '|admin_page:;V;'))) {
	if ($site_page_info['editable'] === 'Y' && strpos($_GET['design_file'], '_tab') === false) {
		$buffer_final .= "<p class=\"AB-btn-edit-page\"><a href='" . $GLOBALS['lib_insiter']->get_sol_url('page_designer.php', array('change_vars'=>array('page_file'=>$_GET['design_file']))) . "' target='_blank'>페이지편집</a></p>";
	}
}

$buffer_final = str_replace('</body>', print_javascript() . '</body>', $buffer_final);
$buffer_final = str_replace('</head>', $GLOBALS['site_config']['log_script_1'] . '</head>', $buffer_final);
$buffer_final = preg_replace("|(<body[^>]*>)|", '$1' . $GLOBALS['site_config']['log_script_2'], $buffer_final);
$buffer_final = str_replace('</body>', $GLOBALS['site_config']['log_script_3'] . '</body>', $buffer_final);

if ($_get_for_email_ === 'Y') {		// 이메일 전송용
	preg_match_all("|<div[^>]+ABA-wrap-box[^>]+>|", $buffer_final, $matches);	// 행박스 sytle 적용
	foreach ($matches[0] as $idx=>$div_tag_src) {
		$div_tag_targ = $div_tag_src;
		if (strpos($div_tag_targ, 'AB-cr') !== false) $div_tag_targ = str_replace('AB-cr', '', $GLOBALS['lib_common']->set_tag_attr($div_tag_targ, 'style', "overflow:hidden;", 'add', ';'));
		$div_tag_targ = str_replace('ABA-wrap-box', '', $div_tag_targ);
		$buffer_final = str_replace($div_tag_src, $div_tag_targ, $buffer_final);
	}
	preg_match_all("|<div[^>]+ABA-layout-align[^>]+>|", $buffer_final, $matches);	// ABA-layout-align sytle 적용
	foreach ($matches[0] as $idx=>$div_tag_src) {
		$div_tag_targ = $div_tag_src;
		if (strpos($div_tag_targ, 'ABA-layout-align') !== false) $div_tag_targ = str_replace('ABA-layout-align', '', $GLOBALS['lib_common']->set_tag_attr($div_tag_targ, 'style', "width:{$GLOBALS['site_config']['site_width']}px;margin:0 auto;", 'add', ';'));
		$div_tag_targ = str_replace('ABA-layout-align', '', $div_tag_targ);
		$buffer_final = str_replace($div_tag_src, $div_tag_targ, $buffer_final);
	}
	preg_match_all("|<div[^>]+ABA-container-box-([0-9]+)[^>]+>|", $buffer_final, $matches);	// 열박스 sytle 적용
	foreach ($matches[0] as $idx=>$div_tag_src) {
		$div_tag_targ = $GLOBALS['lib_common']->set_tag_attr($div_tag_src, 'style', "width:{$matches[1][$idx]}px;float:left;", 'add', ';');
		$div_tag_targ = str_replace('ABA-container-box-' . $matches[1][$idx], '', $div_tag_targ);
		$buffer_final = str_replace($div_tag_src, $div_tag_targ, $buffer_final);
		//echo($matches[1][$idx] . '<br>');
	}
	preg_match_all("|<div[^>]+ABA-content-box[^>]+>|", $buffer_final, $matches);	// 컨텐츠박스 sytle 적용
	foreach ($matches[0] as $idx=>$div_tag_src) {
		$div_tag_targ = $GLOBALS['lib_common']->set_tag_attr($div_tag_src, 'style', "position:relative;margin:0 0 5px 0;", 'add', ';');
		$div_tag_targ = str_replace('ABA-content-box', '', $div_tag_targ);
		$buffer_final = str_replace($div_tag_src, $div_tag_targ, $buffer_final);
	}
	$buffer_final = $GLOBALS['lib_common']->strip_tag_arrays($buffer_final, array('script'), 'Y');
	$buffer_final = $GLOBALS['lib_insiter']->str_to_abs_url($buffer_final);
}

if ($_get_for_print_ === 'Y') {		// 프린트, .doc 다운로드(download_design.php)용)
	$all_tag = $GLOBALS['lib_common']->get_tag_all($buffer_final, '#ABA-only-print', 'G');		// 지정한 부분만 남기고 모두 제거 하는 옵션
	if (count($all_tag[0]) > 0) $buffer_final = preg_replace("|(<body[^>]*>)(.+)(</body>)|s", '$1' . $all_tag[0][0] . '$3', $buffer_final);
	$buffer_final = $GLOBALS['lib_common']->get_tag_all($buffer_final, '.ABA-no-print', 'R');	// 지정한 부분은 마크업에서 제거하는 옵션
	$buffer_final = preg_replace("/<input[^>]+type=['\"]*hidden['\"]*[^>]+>/", '', $buffer_final);
	$buffer_final = preg_replace("/<[^>]+style=['\"]*[^>]*display:none[^>]*['\"]*[^>]*>/", '', $buffer_final);
	$buffer_final = $GLOBALS['lib_common']->strip_tag_arrays($buffer_final, array('form'));
}
	
$buffer_final = $GLOBALS['lib_insiter']->replace_include_string($buffer_final);

echo($buffer_final);
ob_get_flush();
//print_r(ob_list_handlers());

/* 버퍼관련함수들 설명
 ob_start() : 버퍼시작
 ob_get_clean() : 버퍼의 내용을 리턴하고 버퍼를 비우고 버퍼종료 (출력은 없음)
 ob_get_flush() : 버퍼의 내용을 리턴하고 출력 */
 
if ($DIRS['visit'] != '' && ($_GET['AJAX'] !== 'Y' || $_GET['board_name'] == '') && ($GLOBALS['site_config']['use_counter'] === 'Y' || ($GLOBALS['site_config']['use_counter'] === 'O' && $GLOBALS['is_bot'] === 'N'))) include "{$DIRS['visit']}include/visit_log_process.inc.php";		// 로그분석 활성화 조건인 경우

function print_javascript() {
	if ($_GET['_preview_ifrm_'] === 'Y') return '';	// 미리보기 ifrm 에서 호출인 경우
	global $DIRS, $PU_host, $user_info, $VI_min_id_str_cnt, $VI_max_id_str_cnt, $site_page_info;	
	$P_script = '';
	foreach ($GLOBALS['ETC_CODE'] as $key=>$value) {
		if ($value === 'Y') {
			switch ($key) {
				case 'PRIVATE_LAYER' :
					$P_script .= "
						<script>
							if ($('#passwd_box').length == 0) {
								$('body').append(\"<div id='passwd_box' style='display:none; position:absolute; left:0; top:0; z-index:999'><form onsubmit='SYSTEM_input_check(document.TC_FORM_PASSWD);return false;' name='TC_FORM_PASSWD' action='real_time' method='post' id='frm_passwd_box' onsubmit='return false;' data-ajax='false'><div class='bdPassword'><div class='bdHead'><img src='template/DESIGN_mobile/program/theme/01/lock.gif' alt=''><span>비공개 글입니다. 비밀번호를 입력하세요.</span></div><div class='bdBody'><input type='password' name='submit_passwd' value='' class='AB-text' style='width:200px' placeholder='비밀번호입력'><input type='submit' value='입력' class='btn-tpl btn-round-01 btn-pattern-B08 btn-size-10 btn-b'><a href='{$GLOBALS[str_url_end]}' onclick=document.getElementById('passwd_box').style.display='none';><span class='btn-tpl btn-round-01 btn-pattern-B00 btn-size-10 btn-b'>닫기</span></a></div></div></form></div>\");
							}
						</script>
					";
				break;
				case 'SWF_OBJ' :
					$P_script .= "<script type=\"text/javascript\" src=\"swf_object.js\" charset=\"{$GLOBALS['site_config']['char_set']}\"></script>";
				break;
				case 'MEDIA_OBJ' :
					$P_script .= "<script type=\"text/javascript\" src=\"{$DIRS['tools_root']}uniplayer/uniplayer.js\" charset=\"{$GLOBALS['site_config']['char_set']}\"></script>";
				break;
			}
		} else {
			$P_script .= $value;
		}
	}
	// ajax 호출인 경우에도 반영되어야 하면 이 곳에 정의
	$P_script .= "
		<script type=\"text/javascript\">
			<!--
				var server_query_string = '{$_SERVER['QUERY_STRING']}';
				var system_level = '{$GLOBALS['site_config']['system_level']}';
	";
	if (count($GLOBALS['buffer_script']) > 0) {
		$GLOBALS['buffer_script'] = str_replace('<!--', '', str_replace('//-->', '', $GLOBALS['buffer_script']));
		$P_script .= "\n" . implode('', $GLOBALS['buffer_script']) . "\n";
	}
	$return_url = '/' . $GLOBALS['lib_insiter']->get_sol_url($site_page_info['file_name'], array('article_num'=>$_GET['article_num'], 'qsa'=>'Y', 'spi'=>$site_page_info));
	
	foreach ($GLOBALS['JS_CODE'] as $key=>$value) {
		if ($value === 'Y') {
			switch ($key) {
				case 'VERIFY_MULTI_CHECK' :
					$P_script .= "
						function verify_multi_check(form, box_name) {
							frm_els = form.elements;
							cnt = frm_els.length ;
							nm_cnt = box_name.length;
							select_flag = -1;
							for (i=0; i<cnt ; ++i) {
								if ((frm_els[i].type === 'checkbox' || frm_els[i].type === 'radio') && frm_els[i].name.substring(0, nm_cnt) === box_name) {
									if (select_flag === -1) select_flag = 0;
									if (frm_els[i].checked) select_flag = 1;
								}
							}
							return select_flag;
						}
					";
				break;
				case 'MOUSEOVER_VIEW' :
					$P_script .= "
						function mouseover_view(origin_image, img) {
							var source1 = eval(img + '.src');
							var source1_width = eval(img + '.width');
							var source1_height = eval(img + '.height');
							var chk_size = source1_width - source1_height;
							var origin = $('#' + origin_image);
							origin.attr('src', source1);
							// 이미지 왜곡을 방지하고자 할 때
							// 큰 이미지에 fix_dir='가로,세로' 마크업
							var fix_dir = origin.attr('fix_dir');
							if (fix_dir !== undefined) {
								var fix_dir_split = fix_dir.split(',');
								origin.removeAttr('width').removeAttr('height');
								if (chk_size > 0) origin.attr('width', fix_dir_split[0]);
								else origin.attr('height', fix_dir_split[1]);
							}
						}
					";
				break;
				case 'MOUSEOVER_VIEW_WATER_MARK' :
					$P_script .= "
						$('body').on('mouseenter', 'a[grt=Y]', function(e) {
							var corner = $(this).attr('corner');
							var obj_origin = $($(this).attr('obj_targ'));
							var file_name = $(this).attr('file_name');
							var corner = $(this).attr('corner');
							
							var crop = $(this).attr('crop');
							if (crop === undefined) crop = obj_origin.attr('crop');
							if (crop === undefined) crop = 'Y';
							
							var rs = $(this).attr('rs');
							if (rs === undefined) rs = obj_origin.attr('rs');
							if (rs === undefined) rs = 'N';
							
							var ov = $(this).attr('ov');
							if (ov === undefined) ov = obj_origin.attr('ov');
							if (ov === undefined) ov = 'N';
							
							var w, width;
							w = obj_origin.attr('w');
							width = $(this).attr('w');
							if (width === undefined) width = w;
							if (width === undefined) width = obj_origin.width();
							
							var h, height;
							h = obj_origin.attr('h');
							height = $(this).attr('h');
							if (height === undefined) height = h;
							if (height === undefined) height = obj_origin.height();
							
							var AB_dynamic_script = '{$DIRS[tools_root]}get_thumb_nail.php?menu=board&corner=' + urlencode(corner) + '&file_name=' + urlencode(file_name) + '&width=' + width + '&height=' + height + '&crop=' + crop + '&ov=' + urlencode(ov);
							//console.log(AB_dynamic_script);
							$.get(AB_dynamic_script, {}, function(data) {
								//console.log(data);
								data = jQuery.parseJSON(data);
								obj_origin.attr('src', data.path);
								if (rs === 'Y') obj_origin.removeAttr('width').removeAttr('height');
								else obj_origin.attr('width', w).attr('height', h);
							});
							
						});
					";
				break;
				case 'LOGIN_FOCUS' :
					foreach ($GLOBALS['FORM_CONFIG'] as $key=>$val) {
						if ($val['page_type'] === 'LOGIN') {
							$form_name = 'TCSYSTEM_' . $val['page_type'] . '_FORM_' . str_replace('index=', '', $val['idx']);
							//$P_script .= "document.$form_name.user_id.focus();document.$form_name.user_id.focus();";
						}
					}
				break;
				case 'PRIVATE_ARTICLE' :
					//global $site_page_info;
					$P_pw_box_submit_script = "
							if (typeof(passwd_ptr_obj) === 'undefined' || passwd_ptr_obj == '') {
								form.submit();
							} else {
								var passwd_post = {submit_passwd:form.submit_passwd.value};
								$.post(passwd_action, jQuery.extend(passwd_post, passwd_post_vars), function(data) {
									var alert_msg = get_alert_msg_in_result(data);
									if (alert_msg === 'null') {
										if (passwd_post_vars['reload_url'] == '') $(passwd_ptr_obj).html(data);
										else $(passwd_ptr_obj).load(passwd_post_vars['reload_url'] + '&AJAX=Y', function(data) {});
										passwd_box.style.display = 'none';
										form.submit_passwd.value = '';
									} else {
										eval(alert_msg);
									}
								});
							}
					";
					// 패스워드 입력창 띄우기
					$P_script .= "
						function SYSTEM_on_passwd_input(form_action, target, ptr_obj, post_vars) {
							var passwd_box = document.getElementById('passwd_box');
							passwd_action = form_action;
							passwd_ptr_obj = ptr_obj;
							passwd_post_vars = post_vars;
							if (passwd_box.style.display === 'none') {
								passwd_box.style.display = '';
								//var passwd_x = __mouse_xy[0] - 200;
								var passwd_x = document.documentElement.clientWidth/2 - passwd_box.offsetWidth/2;
								var passwd_y = __mouse_xy[1] - 100;
								passwd_box.style.left = passwd_x + 'px';
								passwd_box.style.top = passwd_y + 'px';
								document.TC_FORM_PASSWD.submit_passwd.value = '';
								document.TC_FORM_PASSWD.submit_passwd.focus();
								document.TC_FORM_PASSWD.action = form_action;
								document.TC_FORM_PASSWD.target = target;
							} else {
								passwd_box.style.display = 'none';
							}
						}
						function SYSTEM_input_check(form) {
							if (form.submit_passwd.value == '') {
								alert('비밀번호를 입력하세요');
								form.submit_passwd.focus();
								return false;
							}
							$P_pw_box_submit_script
						}
					";
				break;
				case 'VOTE_ARTICLE' :
					$P_script .= "
						function SYSTEM_vote_article(form, board, serial_num, target, chg_values, after_script, after_msg, frm_attr, alert_msg) {
							if (alert_msg == '') alert_msg = '계속 진행 하시려면 \'확인\'을 그렇지 않으면 \'취소\'를 클릭하세요';
							if (alert_msg === 'X' || confirm(alert_msg)) {
								if (after_msg === 'X') after_msg = '';
								submit_direct_ajax('', '{$DIRS['board_root']}article_vote.php', board, serial_num, target, chg_values, '{$_SERVER['QUERY_STRING']}', after_script, after_msg, frm_attr, '{$PU_host['host']}', '{$GLOBALS['site_config']['index_file']}', '{$return_url}', '{$GLOBALS['admin_mode']}', '{$site_page_info['file_name']}');
							} else {
								return false;
							}
						}
					";
				break;
				case 'MODIFY_ARTICLE' :
					$P_script .= "
						function SYSTEM_modify_article(form, board, serial_num, target, chg_values, after_script, after_msg, frm_attr, alert_msg, modify_sync_rel) {
							if (alert_msg == '') alert_msg = '계속 진행 하시려면 \'확인\'을 그렇지 않으면 \'취소\'를 클릭하세요';
							if (alert_msg === 'X' || confirm(alert_msg)) {
								if (after_msg === 'X') after_msg = '';
								submit_direct_ajax(form, '{$DIRS['board_root']}article_modify.php?is_direct=Y&modify_sync_rel=' + modify_sync_rel, board, serial_num, target, chg_values, '{$_SERVER['QUERY_STRING']}', after_script, after_msg, frm_attr, '{$PU_host['host']}', '{$GLOBALS['site_config']['index_file']}', '{$return_url}', '{$GLOBALS['admin_mode']}', '{$site_page_info['file_name']}');
							} else {
								return false;
							}
						}
					";
				break;
				case 'DELETE_ARTICLE' :
					$P_script .= "
						function SYSTEM_delete_article(form, board, serial_num, target, after_script, after_msg, frm_attr, alert_msg) {
							if (alert_msg == '') alert_msg = '삭제하신 정보는 복구 할 수 없습니다.\\n\\n본 정보를 삭제 하시려면 \'확인\'을 그렇지 않으면 \'취소\'를 클릭하세요.';
							if (alert_msg === 'X' || confirm(alert_msg)) {
								if (after_msg === 'X') after_msg = '';
								submit_direct_ajax(form, '{$DIRS['board_root']}article_delete.php', board, serial_num, target, '', '{$_SERVER['QUERY_STRING']}', after_script, after_msg, frm_attr, '{$PU_host['host']}', '{$GLOBALS['site_config']['index_file']}', '{$return_url}', '{$GLOBALS['admin_mode']}', '{$site_page_info['file_name']}');
							} else {
								return false;
							}
						}
					";
				break;
				case 'CATEGORY_GO' :
					$P_script .= "
						function MM_jumpMenu(targ,selObj,restore){ //v3.0
							if (selObj.tagName === \"SELECT\") {
								eval(targ+\".location='\"+selObj.options[selObj.selectedIndex].value+\"'\");
								if (restore) selObj.selectedIndex = 0;
							} else {
								eval(targ+\".location='\"+selObj.value+\"'\");
								if (restore) selObj.checkedIndex = 0;
							}
						}
					";
				break;
				case 'SELECT_SSL' :																												// 보안로그인 체크상자 만드는 함수
					$T_exp = explode('/', $_SERVER['SCRIPT_NAME']);													// 파일명을 제외한 URL 얻기시작
					unset($T_exp[count($T_exp)-1]);
					$current_path = implode('/', $T_exp);																			// 파일명 제외된 현재경로 (action 파일을 연결시키기 위함)
					$P_script .= "
						var frm_action_src_ssl, cmp_flag;
						function chg_https(obj, form, port) {
							if (typeof(frm_action_src_ssl) === 'undefined') frm_action_src_ssl = form.action;						// 폼의 첫 action 값을 정의
							switch (obj.type) {
								case 'checkbox':
									if (obj.checked === true) cmp_flag = true;
									else cmp_flag = false;
								break;
								case 'radio':
									if (obj.value === 'Y') cmp_flag = true;
									else cmp_flag = false;
								break;
								case 'select-one':
									if (obj.value === 'Y') cmp_flag = true;
									else cmp_flag = false;
								break;
							}
							if (cmp_flag === true) {																									// 체크된 상태면
								u_scheme = 'https';
								frm_action = form.action;																						// action 값 얻음
								port = ':' + port;
								if (frm_action.substring(0, 4) === 'http') {														// 프로토콜 포함된 경로인 경우
									if (frm_action.substring(0, 5) === 'https') frm_action = frm_action.replace('https', '');
									else frm_action = frm_action.replace('http', '');
									form.action = u_scheme + frm_action;
								}	else {																															// 프로토콜 없는 경우
									if (frm_action.substring(0, 1) === '/') {															// 절대경로 인 경우
										form.action = u_scheme + '://{$PU_host[host]}' + port + frm_action;
									} else {
										if (frm_action.substring(0, 2) === './') {														// 상대경로 인 경우
											frm_action = frm_action.replace('./', '');
										}
										form.action = u_scheme + '://{$PU_host[host]}{$current_path}' + port + '/' + frm_action;
									}
								}
							} else {
								form.action = frm_action_src_ssl;
							}
						}
					";
				break;
				case 'JOIN_AGREE' :
					$P_script .= "
						$('body').on('change', '.AB-agree-input-box', function(event) {
							var flag = 0;
							var form = $(this).parents('form');
							var form_id = form.attr('id');
							var ABAIB = $('#' + form_id + ' .AB-agree-input-box');
							ABAIB.each(function(index) {
								switch ($(this).prop('type')) {
									case 'checkbox' :
										if ($(this).val() === 'Y' && $(this).prop('checked') === false) flag++;
										if ($(this).val() !== 'Y' && $(this).prop('checked') === true) flag++;
									break;
									case 'radio' :
										if ($(this).val() === 'Y' && $(this).prop('checked') === false) flag++;
										if ($(this).val() !== 'Y' && $(this).prop('checked') === true) flag++;
									break;
									case 'select-one' :
										if ($(this).val() !== 'Y') flag++;
									break;
								}
								if (flag > 0) $('#T_insiter_join_agree_' + form_id).val('N');
								else $('#T_insiter_join_agree_' + form_id).val('Y');
							});
						});
						function verify_join_agree(form, msg) {
							obj = document.getElementById('T_insiter_join_agree_' + form.id);
							if (typeof(obj) !== 'undefined') {
								if (obj.value === 'Y') cmp_flag = true;
								else cmp_flag = false;
								if (cmp_flag === false) {
									alert(msg);
									return false;
								}
							} else {
								return true;
							}
						}
					";
				break;
			}
		} else {
			$P_script .= $value;
		}
	}

	for ($i=0,$cnt=count($GLOBALS['JS']); $i<$cnt; $i++) {
		$P_script .= str_replace("{$GLOBALS[site_config][replace_str_open_close][0]}ADDSCRIPT{$GLOBALS[site_config][replace_str_open_close][1]}", $GLOBALS['JS_SUMBIT'], $GLOBALS['JS'][$i]) . "\n";	// 모아둔 자바스크립트 출력
	}
	for ($i=0,$cnt=count($GLOBALS['JQS']); $i<$cnt; $i++) {
		if ($i == 0) $P_script .= '$(function() {';
		$P_script .= $GLOBALS['JQS'][$i];
		if ($i == $cnt - 1) $P_script .= '});';
	}
	foreach ($GLOBALS['FORM_SCRIPT'] as $key=>$value) {
		$value = str_replace("{$GLOBALS[site_config][replace_str_open_close][0]}ADDSCRIPTSTART{$GLOBALS[site_config][replace_str_open_close][1]}", $GLOBALS['FORM_SCRIPT_SUBMIT_START'][$key], $value);	// 윗 부분 추가 스크립트 치환
		$P_script .= str_replace("{$GLOBALS[site_config][replace_str_open_close][0]}ADDSCRIPT{$GLOBALS[site_config][replace_str_open_close][1]}", $GLOBALS['FORM_SCRIPT_SUBMIT'][$key], $value) . "\n";	// 유효성 통과 후 추가 스크립트 치환
	}
	$P_script .= '			
			//-->
		</script>
	';
	for ($i=0,$cnt=count($GLOBALS['ETC']); $i<$cnt; $i++) $P_script .= $GLOBALS['ETC'][$i] . "\n";		// 기타 태그 출력(레이어등)
	$GLOBALS['JS_CODE'] = $GLOBALS['JS'] = $GLOBALS['JQS'] = $GLOBALS['JS_SUMBIT'] = $GLOBALS['FORM_SCRIPT'] = $GLOBALS['FORM_SCRIPT_SUBMIT'] = $GLOBALS['ETC_CODE'] = $GLOBALS['ETC'] = array();

	if ($_GET['AJAX'] === 'Y' || strpos($GLOBALS['body_out_tag'], '</body>') === false) echo($P_script);
	else return $P_script;
}

// 타이틀 태그 변경
function real_time_chg_html($site_page_info, $buffer, $speed_print) {
	if ($GLOBALS['chg_title'] == '') $GLOBALS['chg_title'] = $GLOBALS['web_page_title'];
	if ($GLOBALS['chg_description'] == '') $GLOBALS['chg_description'] = $GLOBALS['web_page_meta_desc'];
	if ($GLOBALS['chg_keywords'] == '') $GLOBALS['chg_keywords'] = $GLOBALS['web_page_meta_kw'];
	if (count($GLOBALS['chg_head']) > 0) {
		$add_head = implode("\n", $GLOBALS['chg_head']);
		$buffer = str_replace('<head>', "<head>\n{$add_head}", $buffer);
		$GLOBALS['chg_head'] = array();
	}
	if ($GLOBALS['chg_title'] != '' && $GLOBALS['chg_title'] !== '___C___') {
		//$GLOBALS['chg_title_cnt']++;
		if (preg_match('|<title>.*</title>|i', $buffer, $matches)) $buffer = str_replace($matches[0], "<title>{$GLOBALS['chg_title']}</title>", $buffer);
		else $buffer = str_ireplace('</head>', "<title>{$GLOBALS['chg_title']}</title></head>", $buffer);
		$buffer = preg_replace('|<meta name="title" content=.+\/>|i', '<meta name="title" content="' . $GLOBALS['chg_title'] . '" />', $buffer);
		$buffer = preg_replace('|<meta name="subject" content=.+\/>|i', '<meta name="subject" content="' . $GLOBALS['chg_title'] . '" />', $buffer);
		if ($GLOBALS['chg_description'] != '') {
			$GLOBALS['chg_description'] = str_replace('$', '\$', $GLOBALS['chg_description']);
			$buffer = preg_replace('|<meta name="description" content=.+\/>|i', '<meta name="description" content="' . $GLOBALS['chg_description'] . '" />', $buffer);
			$buffer = preg_replace('|<meta property="og:description" content=.+\/>|i', '<meta property="og:description" content="' . $GLOBALS['chg_description'] . '" />', $buffer);
			$GLOBALS['chg_description'] = '';
		}
		if ($GLOBALS['chg_keywords'] != '') {
			$GLOBALS['chg_keywords'] = str_replace('$', '\$', $GLOBALS['chg_keywords']);
			$buffer = preg_replace('|<meta name="keywords" content=.+\/>|i', '<meta name="keywords" content="' . $GLOBALS['chg_keywords'] . '" />', $buffer);
			$GLOBALS['chg_keywords'] = '';
		}
		$GLOBALS['chg_title'] = '___C___';
	}
	$buffer = str_replace('__NAVI__', '', $buffer);
	$buffer = str_replace('__TITLE__', '', $buffer);
	
	// 제거할 속성들
	$buffer = str_replace(' microid="Y"', '', $buffer);
	
	//$_SERVER['HTTP_USER_AGENT'] = 'W3C_Validator/1.3';
	$is_validator = 'N';
	if (stripos($_SERVER['HTTP_USER_AGENT'] , 'W3C_Validator') !== false) {
		$is_validator = 'Y';
		$rm_attr = array('-rov', 'exec-func', 'submit-func', 'vi', 'effect-1', 'effect-2', 'effect-lnb', 'effect-mobile', 'sub-w-fit', 'wide-parent', 'sub-l-fit', 'a-speed', 'a-show', 'a-hide', 'default-view', 'toggle-hour', 'toggle-class', 'align', 'valign', 'width', 'height', 'cellpadding', 'cellspacing', 'border', 'union-article', '__dfg', 'oc');
		foreach ($rm_attr as $attr_name) $buffer = preg_replace("/ {$attr_name}=['\"][^'\"]*['\"]/i", '', $buffer);
	}
	
	$skip_dco_opt = 'N';
	if ($site_page_info['is_admin'] === 'Y') $skip_dco_opt = 'Y';
	if (stripos($buffer, '<!--[if IE ') !== false) $skip_dco_opt = 'Y';	// script/style 위치 변경과, 공백제거 로직이 수행되면 안되는 경우 여기 까지만.
	if ($speed_print === 'Y') $skip_dco_opt = 'Y';
	if ($_GET['AJAX'] === 'Y') $skip_dco_opt = 'Y';
	if ($skip_dco_opt === 'Y') return $buffer;
	
	$pattern = "|(<script[^>\.]*>)(.+?)(</script[^>]*>)|is";	// 흩어져 있는 script 위치를 헤더로 이동하기 위해 모음
	preg_match_all($pattern, $buffer, $matches);
	for ($i=0,$cnt=count($matches[2]); $i<$cnt; $i++) {
		if (strpos($matches[0][$i], ' not-move="Y"') !== false) continue;
		$buffer = str_replace($matches[0][$i], '', $buffer);
		$GLOBALS['buffer_script'][] = $matches[2][$i];
	}
	$pattern = "|(<style[^>\.]*>)(.+?)(</style[^>]*>)|is";	// 흩어져 있는 style 위치를 헤더로 이동하기 위해 모음
	preg_match_all($pattern, $buffer, $matches);
	for ($i=0,$cnt=count($matches[2]); $i<$cnt; $i++) {
		if (strpos($matches[0][$i], ' not-move="Y"') !== false) continue;
		$buffer = str_replace($matches[0][$i], '', $buffer);
		$GLOBALS['buffer_style'][] = $matches[2][$i];
	}
	$pattern = "|<link href[^>]+stylesheet[^>]+>|is";			// 흩어져 있는 style 파일 위치를 헤더로 이동하기 위해 모음
	preg_match_all($pattern, $GLOBALS['lib_common']->strip_tag_arrays($buffer, array('head'), 'Y'), $matches);
	for ($i=0,$cnt=count($matches[0]); $i<$cnt; $i++) {
		if (strpos($matches[0][$i], ' not-move="Y"') !== false) continue;
		$buffer = str_replace($matches[0][$i], '', $buffer);
		$GLOBALS['buffer_style_file'][] = $matches[0][$i];
	}
	$buffer = str_replace(' not-move="Y"', '', $buffer);
	
	if ($is_validator === 'N') {
		$search = array(		// 공백 등 제거
			'/\>[^\S ]+/s',	// strip whitespaces after tags, except space
			'/[^\S ]+\</s',	// strip whitespaces before tags, except space
			'/(\s)+/s'			// shorten multiple whitespace sequences
		);
		$replace = array(
			'>',
			'<',
			'\\1'
		);
		$buffer = preg_replace($search, $replace, $buffer);
	}
	return $buffer;
}

// 게시물항목(정보) 출력함수
// $board_info_src : 항목과는 별개로 연결테이블이 존재 할 때는 해당(연결) 게시판정보가 저장되어 넘어옴
// $board_info : 함수 내부에서 사용되는 게시판정보로 연결글 항목일 때는 해당 연결글의 게시판 이름으로 덮어 씌워 사용됨(오버로딩)
// 링크주소를 만들 때 사용되는 board_info 는 연결게시물 여부와는 관계없이 원래 넘어온 board_info_src 를 사용하되 연결글 링크인경우 board_info_src 를 오버로딩
function make_article_value($board_info_src, $form_config, $exp, $user_info) {
	global $root, $DB_TABLES, $DIRS, $site_page_info;
	
	$board_info = $board_info_src;
	if (count($board_info['child']) > 0) $board_info = $board_info['child'];

	$article_item = $exp['1'];
	$exp_prt_type = $GLOBALS['lib_insiter']->explode_sol_div($GLOBALS['DV']['ct4'], $exp['2']);											// 출력속성 불러옴
	$prt_type = $exp_prt_type['0'];
	$exp_pp_item = $GLOBALS['lib_insiter']->explode_sol_div($GLOBALS['DV']['ct4'], $exp['3']);											// 필드값 전용 속성 불러옴
	$item_index = $exp_pp_item['0'];

	// 답변이나 코멘트 쓰기폼에서 넘어오는 저장값은 원글 정보로 별도 취급함
	if ($form_config['page_type'] !== 'REPLY' && $form_config['page_type'] !== 'COMMENT') {
		$article_value = $form_config['article_value_one'];
	} else {
		$article_value_src = $form_config['article_value_one'];
		$article_value = array();
	}
	
	if (preg_match('|user_file_[0-9]+|', $article_item) || preg_match('|category_[0-9]+|', $article_item)) {
		$T_exp = explode('_', $article_item);
		$item_index = array_pop($T_exp);
		$article_item = implode('_', $T_exp);
	}
	
	$article_item_index = $article_item;
	if ($item_index != '' && $article_item !== 'user_file' && $article_item !== 'user_file_dn_cnt' && $article_item !== 'file_size' && $article_item !== 'file_date' && $article_item !== 'total_relation' && $prt_type !== 'F') $article_item_index = $article_item . "_{$item_index}";

	// 게시물정보 오버로딩
	if ($exp['4'] === 'P') {																						// 연결될게시판(원글)
		$article_value = $article_value_src;
	} else if ($exp['4'] === 'R') {																				// 연결된게시판(연결글)
		$rel_info = $GLOBALS['lib_insiter']->explode_sol_div($GLOBALS['DV']['ct4'], $exp['5']);
		$article_value_rel = $GLOBALS['lib_insiter']->get_article_relation($article_value, array($rel_info['0']));	// 연결글 레코드 추출
		if ($rel_info['1'] !== 'Y') {
			if (count($board_info['child']) > 0) $board_info = $board_info['child'];		// 연결글 board_info 로 오버로딩
			else $board_info = $GLOBALS['lib_fix']->get_board_info($article_value['relation_table_' . $rel_info['0']]);
		}
		$article_value[$article_item_index] = $article_value_rel[$article_item_index];			// 오버로딩 해야 할 필드만 변경 (필드 하나씩 출력하는 방식인 점, 링크 주소 등 아래 쪽에서는 원본 정보가 활용된다는 점 기억할 것)
		$article_value['writer_id'] = $article_value_rel['writer_id'];									// 작성자 (개인정보 열람 때문에 추가 했는데, 현재 필드 이외의 다른 필드값을 활용해야 하는 부분이 있다면 다른 필드도 추가될 수 있음)
		$article_value['mini_uid'] = $article_value_rel['mini_uid'];
		$article_value['prev_id'] = $article_value_rel['prev_id'];
		$article_value['DBAN'] = $article_value_rel['DBAN'];												// 게시판 이름
		$article_value['DBTBN'] = $article_value_rel['DBTBN'];											// 게시판 테이블 이름
		$article_value['DBLPN'] = $article_value_rel['DBLPN'];											// 게시판 목록 페이지
		$article_value['DBVPN'] = $article_value_rel['DBVPN'];											// 게시판 보기 페이지
		$article_value['DBWPN'] = $article_value_rel['DBWPN'];											// 게시판 쓰기 페이지
		$article_value['DBMPN'] = $article_value_rel['DBMPN'];											// 게시판 수정 페이지
		$article_value['DBDPN'] = $article_value_rel['DBDPN'];											// 게시판 삭제 페이지
		$article_value['DBRPN'] = $article_value_rel['DBRPN'];											// 게시판 답변 페이지
		$article_value['user_file'] = $article_value_rel['user_file'];									// 첨부파일(서버)
		$article_value['user_file_real'] = $article_value_rel['user_file_real'];					// 첨부파일(사용자)
		$article_value['cnt_download'] = $article_value_rel['cnt_download'];							// 첨부파일(다운로드수)
		$article_value['file_size'] = $article_value_rel['file_size'];									// 첨부파일(등록일)
		$article_value['file_date'] = $article_value_rel['file_date'];									// 첨부파일(등록일)
		$article_value['is_html'] = $article_value_rel['is_html'];
	} else {																												// 현재게시판(기본글 목록, 상세보기..)
		//
	}
	
	// 회원정보라면 아래 루틴 수행
	if ($exp['7'] != '') {
		$exp_7 = $GLOBALS['lib_insiter']->explode_sol_div($GLOBALS['DV']['ct4'], $exp['7']);
		if ($exp_7[0] !== 'X') {
			if ($exp_7[0] === 'A') {
				$article_value = $GLOBALS['lib_fix']->get_user_info($article_value['writer_id']);
			} else if ($exp_7[0] === 'M') {
				$article_value = $GLOBALS['lib_fix']->get_user_info($article_value['mini_uid']);
			} else if ($exp_7[0] === 'P') {
				$article_value = $GLOBALS['lib_fix']->get_user_info($article_value['prev_id']);
			} else if ($exp_7[0] === 'V') {
				$T_tag = array();
				$T_exp_id = explode(';', $article_value['u_viewer']);
				for ($T_i=0,$cnt=count($T_exp_id); $T_i<$cnt; $T_i++) {
					if ($T_exp_id[$T_i] == '') continue;
					$form_config['article_value_one'] = $GLOBALS['lib_fix']->get_user_info($T_exp_id[$T_i]);
					$form_config['article_value_one'] = $GLOBALS['lib_insiter']->get_article_file($DB_TABLES['member'], $form_config['article_value_one'], 'Y');	// 첨부파일 연동 적용
					$exp[7] = '';
					$GLOBALS['u_viewer_list'][$T_exp_id[$T_i]]['user_info'] = $form_config['article_value_one'];
					$T_idx = $article_item_index;
					if ($item_index != '') $T_idx .= '_' . $item_index;
					$GLOBALS['u_viewer_list'][$T_exp_id[$T_i]][$T_idx] = $T_tag[] = make_article_value($board_info_src, $form_config, $exp, $user_info, $form_config['current_page']);
				}
				return implode(', ', $T_tag);
			} else if ($exp_7[0] === 'G') {
				$article_value = $GLOBALS['lib_fix']->get_user_info($_GET[$exp_7[1]]);
			} else {
				$article_value = $user_info;
			}
			$board_info = $board_info_src = $GLOBALS['lib_fix']->get_board_info($DB_TABLES['member']);			// 회원게시판 으로 변경
			$article_value = $GLOBALS['lib_insiter']->get_article_file($DB_TABLES['member'], $article_value);	// 첨부파일 연동 적용
		}
	}
	
	$exp_link_info = $GLOBALS['lib_insiter']->explode_sol_div($GLOBALS['DV']['ct4'], $exp['6']);	// 링크정보 불러옴
	$pp_link_rollover = $exp_link_info['3'];
	$pp_link_rollover_info = $exp_link_info['20'];
	
	$sch_emp = 'N';															// 검색 키워드 강조여부
	switch ($article_item_index) {										// 출력될 값 설정
		case 'total_article' :
			$T_value = $form_config['total_record'];
		break;
		case 'total_reply' :
			$query_rep = "select count({$board_info['fld_name_idx']}) from {$board_info['tbl_name']} where fid='{$article_value['fid']}' and thread<>''";
			$result_rep = $GLOBALS['lib_common']->querying($query_rep);
			$T_total = mysql_fetch_row($result_rep);
			$GLOBALS['renew_AV'] = array();																								// 게시물 레코드에 실시간 항목을 추가하기 위한
			$total_rep = $form_config['article_value_one']['total_rep'] = $GLOBALS['renew_AV']['total_rep'] = $T_total['0'];
			if ($total_rep === '0' && $prt_type === 'N') return '';
			$T_value = $total_rep;
		break;
		case 'total_relation' :
			$fn_table = "relation_table_{$item_index}";
			$fn_serial = "relation_serial_{$item_index}";
			if ($exp_pp_item['1'] == '') $exp_pp_item['1'] = $GLOBALS['site_config']['comment_table_name'];
			if ($exp_pp_item['2'] == '') $exp_pp_item['2'] = $article_value['DBTBN'];
			if ($exp_pp_item['3'] == '') $exp_pp_item['3'] = 'serial_num';
			if ($exp_pp_item['4'] != '') $relation_sub_query = ' and ' . $exp_pp_item['4'];
			else $relation_sub_query = '';
			$r_board_info = $GLOBALS['lib_fix']->get_board_info($exp_pp_item['1']);
			$query_rel = "select count({$board_info['fld_name_idx']}) from {$r_board_info['tbl_name']} where {$fn_table}='{$exp_pp_item['2']}' and {$fn_serial}='{$article_value[$exp_pp_item[3]]}'{$relation_sub_query}";
			$result_rel = $GLOBALS['lib_common']->querying($query_rel);
			$T_total = mysql_fetch_row($result_rel);
			$fn_total_rel = "total_rel_{$item_index}";
			$GLOBALS['renew_AV'] = array();																								// 게시물 레코드에 실시간 항목을 추가하기 위한
			$total_rel = $form_config['article_value_one'][$fn_total_rel] = $GLOBALS['renew_AV'][$fn_total_rel] = $T_total['0'];
			if ($total_rel === '0' && $prt_type === 'N') return '';
			$T_value = $total_rel;
		break;
		case 'asc_num' :
/*			if ($form_config['article_value_one']['is_view'] !== 'N')*/ $T_value = $article_value[$article_item_index];
//			else $T_value = "<img src=\"{$DIRS['designer_thema']}images/cancel.png\" border=0 />";
		break;
		case 'desc_num' :
/*			if ($form_config['article_value_one']['is_view'] !== 'N')*/ $T_value = $article_value[$article_item_index];
//			else $T_value = "<img src=\"{$DIRS['designer_thema']}images/cancel.png\" border=0 />";
		break;
		case 'user_file' :																																	// 업로드 파일은 한 필드에 여러 파일명이 ; 로 구분되어 저장되므로 지정된 인덱스를 찾아야 함
			$saved_upload_files = explode(';', $article_value['user_file']);
			$T_value = $saved_upload_files[$item_index-1];
		break;
		case 'user_file_dn_cnt' :
			$cnt_download = explode(';', $article_value['cnt_download']);
			$T_value = $cnt_download[$item_index-1];
		break;
		case 'file_size' :
			$file_size = explode(';', $article_value['file_size']);
			if($file_size[$item_index-1]) $T_value = $file_size[$item_index-1] / 1024;
		break;
		case 'file_date' :
			$file_date = explode(';', $article_value['file_date']);
			$T_value = $file_date[$item_index-1];
		break;
		case 'writer_name' :
			global $IS_x_names;
			$T_value = stripslashes($article_value[$article_item_index]);
			$article_auth_info = $GLOBALS['lib_insiter']->get_article_auth($board_info, $article_value, $user_info, 'admin', '', $form_config);
			if ($article_auth_info !== 'O' && $board_info['fn_p_info_replace'] != '') {					// 개인정보 대체필드명이 설정된 경우 해당 필드에 저장된 id 로 회원정보검색 후 대체함
				$user_info_p_info = $GLOBALS['lib_fix']->get_user_info($article_value[$board_info['fn_p_info_replace']]);
				if ($user_info_p_info['mb_name'] != '') $T_value = $user_info_p_info['mb_name'];
			}
			$article_auth_info = $GLOBALS['lib_insiter']->get_article_auth($board_info, $article_value, $user_info, 'p_info', '', $form_config);
			if ($article_auth_info !== 'O' && !in_array($T_value, $IS_x_names)) $T_value = $GLOBALS['lib_common']->str_cutstring($T_value, $GLOBALS['site_config']['kr_byte'], $GLOBALS['site_config']['sec_char']['name']);
			$sch_emp = 'Y';
		break;
		case 'email' :
			$T_value = stripslashes($article_value[$article_item_index]);
			$article_auth_info = $GLOBALS['lib_insiter']->get_article_auth($board_info, $article_value, $user_info, 'admin', '', $form_config);
			if ($article_auth_info !== 'O' && $board_info['fn_p_info_replace'] != '') {					// 개인정보 대체필드명이 설정된 경우 해당 필드에 저장된 id 로 회원정보검색 후 대체함 (아래 U_address 까지)
				$user_info_p_info = $GLOBALS['lib_fix']->get_user_info($article_value[$board_info['fn_p_info_replace']]);
				if ($user_info_p_info['email'] != '') $T_value = $user_info_p_info['email'];
			}
			$article_auth_info = $GLOBALS['lib_insiter']->get_article_auth($board_info, $article_value, $user_info, 'p_info', '', $form_config);
			if ($article_auth_info !== 'O') {
				$T_value = $GLOBALS['site_config']['sec_char']['email'];
				$F_link_disable = 'Y';
			}
			$sch_emp = 'Y';
		break;
		case 'homepage' :
			$T_value = stripslashes($article_value[$article_item_index]);
			if (substr($T_value, -1) === '/') $T_value = substr($T_value, 0, -1);
			$article_auth_info = $GLOBALS['lib_insiter']->get_article_auth($board_info, $article_value, $user_info, 'admin', '', $form_config);
			if ($article_auth_info !== 'O' && $board_info['fn_p_info_replace'] != '') {
				$user_info_p_info = $GLOBALS['lib_fix']->get_user_info($article_value[$board_info['fn_p_info_replace']]);
				if ($user_info_p_info['homepage'] != '') $T_value = $user_info_p_info['homepage'];
			}
			$article_auth_info = $GLOBALS['lib_insiter']->get_article_auth($board_info, $article_value, $user_info, 'p_info', '', $form_config);
			if ($article_auth_info !== 'O' && $GLOBALS['site_config']['sec_char']['homepage'] != '') {
				$T_value = $GLOBALS['site_config']['sec_char']['homepage'];
				$F_link_disable = 'Y';
			}
			//if ($prt_type !== 'F') $T_value = str_ireplace('http://', '', $T_value);
			$sch_emp = 'Y';
		break;
		case 'phone_1' :
		case 'phone_2' :
		case 'phone_3' :
			$T_value = stripslashes($article_value[$article_item_index]);
			$article_auth_info = $GLOBALS['lib_insiter']->get_article_auth($board_info, $article_value, $user_info, 'admin', '', $form_config);
			if ($article_auth_info !== 'O' && $board_info['fn_p_info_replace'] != '') {
				$user_info_p_info = $GLOBALS['lib_fix']->get_user_info($article_value[$board_info['fn_p_info_replace']]);
				if ($article_item_index === 'phone_1') $fn_phone = 'phone';
				else if ($article_item_index === 'phone_2') $fn_phone = 'phone_mobile';
				else if ($article_item_index === 'phone_3') $fn_phone = 'phone_fax';
				if ($user_info_p_info[$fn_phone] != '') $T_value = $user_info_p_info[$fn_phone];
			}
			$article_auth_info = $GLOBALS['lib_insiter']->get_article_auth($board_info, $article_value, $user_info, 'p_info', '', $form_config);
			if ($article_auth_info !== 'O') {
				$T_value = $GLOBALS['site_config']['sec_char']['phone'];
				$F_link_disable = 'Y';
			}
			$sch_emp = 'Y';
		break;
		case "{$GLOBALS['site_config']['user_define_field_head']}_address" :
			$T_value = stripslashes($article_value[$article_item_index]);
			$article_auth_info = $GLOBALS['lib_insiter']->get_article_auth($board_info, $article_value, $user_info, 'admin', '', $form_config);
			if ($article_auth_info !== 'O' && $board_info['fn_p_info_replace'] != '') {
				$user_info_p_info = $GLOBALS['lib_fix']->get_user_info($article_value[$board_info['fn_p_info_replace']]);
				if ($user_info_p_info['address'] != '') $T_value = $user_info_p_info['address'];
			}
			$article_auth_info = $GLOBALS['lib_insiter']->get_article_auth($board_info, $article_value, $user_info, 'p_info', '', $form_config);
			if ($article_auth_info !== 'O') {
				$exp_address = explode(' ', $T_value);
				$T_value = "{$exp_address['0']} {$exp_address['1']} {$GLOBALS[site_config][sec_char][name]}{$GLOBALS[site_config][sec_char][name]}{$GLOBALS[site_config][sec_char][name]}";
			}
			$sch_emp = 'Y';
		break;
		case 'user_ip' :
			$article_auth_info = $GLOBALS['lib_insiter']->get_article_auth($board_info, $article_value, $user_info, 'p_info', '', $form_config);
			if ($article_auth_info !== 'O') {
				$T_exp_ip = explode('.', $article_value['user_ip']);
				$T_exp_ip['2'] = 'xxx';
				$T_value = implode('.', $T_exp_ip);
			} else {
				$T_value = $article_value['user_ip'];
			}
			$sch_emp = 'Y';
		break;
		case 'page_block' :															// 페이지 링크
			$page_block_link_file_name = $exp_pp_item['7'];					// 링크파일명
			if ($page_block_link_file_name == '' && $site_page_info['v_file_name'] != '') $page_block_link_file_name = $site_page_info['v_file_name'];
			array_shift($exp_pp_item);												// style 설정 영역을 함께 사용하는 구조라 번거로운 작업 추가됨
			$exp_pp_item['6'] = $exp_pp_item['9'];								// 첫 페이지 버튼 파일명
			$exp_pp_item['7'] = $exp_pp_item['10'];							// 마지막 페이지 버튼 파일명
			$exp_pp_item['9'] = $exp_pp_item['11'];							// 링크 마지막 문자열(A NAME)
			$exp_pp_item['10'] = $exp_pp_item['12'];							// 링크 속성(A attr)
			array_splice($exp_pp_item, 11);
			$page_block_info = $GLOBALS['lib_insiter']->get_page_block($form_config['total_record'], $form_config['tpa'], $form_config['tpb'], $form_config['current_page'], $exp_pp_item, $font, "{$DIRS['designer_thema']}images/", $page_block_link_file_name, 'N', $form_config['page_block_page_var_name'], 'C', array('design_file'=>$page_block_link));
			$T_value = $page_block_info['0'];
		break;
		case 'total_page' :
			$T_value = $form_config['total_page'];
			/*if ($form_config['total_record'] > 0) {
				if ((int)$form_config['tpa'] > 0) $T_value = ceil($form_config['total_record'] / $form_config['tpa']);
				else $T_value = '1';
			} else {
				 $T_value = '0';
			}*/
		break;
		case 'current_page' :
			//if ($_GET[$form_config['page_block_page_var_name']] != '') $T_value = $_GET[$form_config['page_block_page_var_name']];
			//else $T_value = 1;
			$T_value = $form_config['current_page'];
			if ($form_config['total_record'] == 0) $T_value = 0;
		break;
		case 'writer_id' :															// 보안상 관리자 아이디는 공개 안함
			$writer_info = $GLOBALS['lib_fix']->get_user_info($article_value[$article_item_index]);
			if ($writer_info['id'] != '') {
				if ($writer_info['user_level'] <= $GLOBALS['site_config']['admin_level'] && $user_info['user_level'] > $GLOBALS['site_config']['admin_level']) $T_value = '*관리자ID';
				else $T_value = stripslashes($article_value[$article_item_index]);
			} else {
				$T_value = $article_value[$article_item_index];
				$exp['6'] = '';
			}
			$sch_emp = 'Y';
		break;
		case 'u_viewer' :
			$T_value = $GLOBALS['lib_common']->strip_bracket($article_value[$article_item_index], ';');
		break;
		case 'cyber_money' :
			$T_value = $GLOBALS['lib_insiter']->get_mb_cyber_money($user_info['id']);
		break;
		case 'cybmn_total' :
			$T_value = $GLOBALS['lib_insiter']->get_mb_cyber_money($user_info['id'], '', '+');
		break;
		case 'buy_cnt' :
			$T_value = get_member_sell_cnt($user_info['id']);
		break;
		default :																		// 이외 모든 필드
			if ($prt_type === 'F') {																				// 필드저장형 첨부파일용
				$exp_aii = explode('_', $article_item_index);
				$item_index = array_pop($exp_aii);
				$saved_upload_files = explode(';', $article_value[implode('_', $exp_aii)]);
				$T_value = $article_value[$article_item_index] = $saved_upload_files[$item_index-1];
				$article_value['user_file'] = $article_value['user_file_real'] = $article_value[$article_item];
			} else {
				$T_value = $article_value[$article_item_index];
			}
			$sch_emp = 'Y';
		break;
	}

	// 코드 출력 타입이 아니고 필드에 값이 없는 경우(코드 타입은 빈 값에도 출력 내용이 부여될 수 있음)
	if ($article_value[$article_item_index] == '' && $prt_type !== 'C'/* && $exp['4'] === 'P'*/) {
		$skip_fld = array('user_file', 'user_file_dn_cnt', 'subject', 'page_block', 'asc_num', 'desc_num', 'total_article', 'total_reply', 'total_relation', 'total_page', 'current_page', 'bd_name', 'viewer_count', 'viewer_count_my', 'viewer_count_oth');	// 패스할 가상 필드들
		if (!in_array($article_item_index, $skip_fld)) return '';		// 패스할 가상 필드를 제외하고는 빈 값 리턴
	}
	switch ($prt_type) {																																// 출력될 값에 출력 형태별(텍스트, 숫자, 코드값 등등) 전용 속성을 적용한다.
		case 'T' :																																		// 텍스트
			$max_string = $exp_prt_type['1'];
			if ($max_string != '') {																							
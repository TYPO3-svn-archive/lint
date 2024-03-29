<?php


require_once t3lib_extMgm::extPath('lint').'classes/class.tx_lint_exception.php';
require_once t3lib_extMgm::extPath('lint').'classes/class.tx_lint_checker.php';


class ux_tslib_cObj extends tslib_cObj {
	
	
	/**
	 * @var tx_lint_checker
	 */
	protected $lintChecker;
	
	
	
	/**
	 * Get link checker
	 * 
	 * @return tx_lint_checker
	 */
	public function getLinkChecker() {
		if (!($this->lintChecker instanceof tx_lint_checker)) {
			$this->lintChecker = t3lib_div::makeInstance('tx_lint_checker');
		}
		return $this->lintChecker;
	}




	/***********************************************
	 *
	 * CONTENT_OBJ:
	 *
	 ***********************************************/

	
	/**
	 * Rendering of a "numerical array" of cObjects from TypoScript
	 * Will call ->cObjGetSingle() for each cObject found and accumulate the output.
	 *
	 * @param	array		$setup: Array with cObjects as values.
	 * @param	string		$addKey: A prefix for the debugging information
	 * @return	string		Rendered output from the cObjects in the array.
	 * @see cObjGetSingle()
	 */
	function cObjGet($setup,$addKey='')	{
		// $this->getLinkChecker()->checkArray($setup);
		return parent::cObjGet($setup, $addKey);
	}

	/**
	 * Renders a content object
	 *
	 * @param	string		The content object name, eg. "TEXT" or "USER" or "IMAGE"
	 * @param	array		The array with TypoScript properties for the content object
	 * @param	string		A string label used for the internal debugging tracking.
	 * @return	string		cObject output
	 * @example http://typo3.org/doc.0.html?&encryptionKey=&tx_extrepmgm_pi1[extUid]=267&tx_extrepmgm_pi1[tocEl]=153&cHash=7e74f4d331
	 */
	function cObjGetSingle($name, $conf, $TSkey='__')	{
		
		if (substr($name,0,1) != '<') {
			$name = trim($name);
			$validObjects = array(				 
				'COBJ_ARRAY', 'COA', 'COA_INT', 'HTML', 'TEXT', 'CLEARGIF', 'FILE', 'IMAGE', 'IMG_RESOURCE', 'IMGTEXT',
	 			'CONTENT', 'RECORDS', 'HMENU', 'CTABLE', 'OTABLE', 'COLUMNS', 'HRULER', 'CASE', 'LOAD_REGISTER', 'RESTORE_REGISTER',
	 			'FORM', 'SEARCHRESULT', 'PHP_SCRIPT', 'PHP_SCRIPT_EXT', 'PHP_SCRIPT_INT', 'USER', 'USER_INT', 'TEMPLATE', 'EDITPANEL',
	 			'MULTIMEDIA', 'MEDIA', 'SWFOBJECT', 'QTOBJECT'
			);
			
			if (!(empty($name) && empty($conf))) {
				if (empty($name)) {
					throw new tx_lint_exception('No name given');
				}
				if (!in_array($name, $validObjects)) {
					// TODO check if there is a hook in $TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClassDefault']
					throw new tx_lint_exception(sprintf('"%s" is not a valid object', htmlspecialchars($name)));
				}
				if (!in_array($name, array('RESTORE_REGISTER')) && empty($conf)) {
					throw new tx_lint_exception(sprintf('Configuration for "%s" is empty', htmlspecialchars($name)));
				}
			}
		}
		
		return parent::cObjGetSingle($name, $conf, $TSkey);
	}
		
		







	/********************************************
	 *
	 * Functions rendering content objects (cObjects)
	 *
	 ********************************************/

	/**
	 * Rendering the cObject, CLEARGIF
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=355&cHash=70c0f19915
	 */
	function CLEARGIF($conf) {
		$this->getLinkChecker()->check($conf, 'CLEARGIF');
		return parent::CLEARGIF($conf);
	}

	/**
	 * Rendering the cObject, COBJ_ARRAY / COA and COBJ_ARRAY_INT
	 *
	 * @param	array		Array of TypoScript properties
	 * @param	string		If "INT" then the cObject is a "COBJ_ARRAY_INT" (non-cached), otherwise just "COBJ_ARRAY" (cached)
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=351&cHash=a09db0329c
	 */
	function COBJ_ARRAY($conf,$ext='')	{

		if (is_array($conf)) {
			$content = '';
			switch($ext) {
				case 'INT':
					$substKey = $ext . '_SCRIPT.' . $GLOBALS['TSFE']->uniqueHash();
					$content .= '<!--'.$substKey.'-->';
					$GLOBALS['TSFE']->config[$ext . 'incScript'][$substKey] = array (
						'file' => $conf['includeLibs'],
						'conf' => $conf,
						'cObj' => serialize($this),
						'type' => 'COA'
					);
				break;
				default:
					if ($this->checkIf($conf['if.'])) {
						$this->includeLibs($conf);
						$content = $this->cObjGet($conf);
						if ($conf['wrap']) {
							$content = $this->wrap($content, $conf['wrap']);
						}
						if ($conf['stdWrap.']) {
							$content = $this->stdWrap($content, $conf['stdWrap.']);
						}
					}
				break;
			}
			return $content;
		} else {
			$GLOBALS['TT']->setTSlogMessage('No elements in this content object array (COBJ_ARRAY, COA, COA_INT).', 2);
		}
	}

	/**
	 * Rendering the cObject, USER and USER_INT
	 *
	 * @param	array		Array of TypoScript properties
	 * @param	string		If "INT" then the cObject is a "USER_INT" (non-cached), otherwise just "USER" (cached)
	 * @return	string		Output
	 * @link	http://typo3.org/documentation/document-library/references/doc_core_tsref/4.1.0/view/8/22/
	 */
	function USER($conf, $ext = '') {
		$content = '';
		switch ($ext) {
			case 'INT':
				$this->userObjectType = self::OBJECTTYPE_USER_INT;
				$substKey = $ext . '_SCRIPT.' . $GLOBALS['TSFE']->uniqueHash();
				$content.='<!--' . $substKey . '-->';
				$GLOBALS['TSFE']->config[$ext . 'incScript'][$substKey] = array(
					'file' => $conf['includeLibs'],
					'conf' => $conf,
					'cObj' => serialize($this),
					'type' => 'FUNC'
				);
				break;
			default:
				if ($this->userObjectType === false) {
					// Come here only if we are not called from $TSFE->INTincScript_process()!
					$this->userObjectType = self::OBJECTTYPE_USER;
				}
				$this->includeLibs($conf);
				$tempContent = $this->callUserFunction($conf['userFunc'], $conf, '');
				if ($this->doConvertToUserIntObject) {
					$this->doConvertToUserIntObject = false;
					$content = $this->USER($conf, 'INT');
				} else {
					$content .= $tempContent;
				}
				break;
		}
		$this->userObjectType = false;
		return $content;
	}

	/**
	 * Retrieves a type of object called as USER or USER_INT. Object can detect their
	 * type by using this call. It returns OBJECTTYPE_USER_INT or OBJECTTYPE_USER depending on the
	 * current object execution. In all other cases it will return false to indicate
	 * a call out of context.
	 *
	 * @return	mixed	One of OBJECTTYPE_ class constants or false
	 */
	public function getUserObjectType() {
		return $this->userObjectType;
	}

	/**
	 * Requests the current USER object to be converted to USER_INT.
	 *
	 * @return	void
	 */
	public function convertToUserIntObject() {
		if ($this->userObjectType !== self::OBJECTTYPE_USER) {
			$GLOBALS['TT']->setTSlogMessage('tslib_cObj::convertToUserIntObject() ' .
				'is called in the wrong context or for the wrong object type', 2);
		}
		else {
			$this->doConvertToUserIntObject = true;
		}
	}

	/**
	 * Rendering the cObject, FILE
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=352&cHash=379c60f8bc
	 */
	function FILE($conf)	{
		$theValue = $this->fileResource($this->stdWrap($conf['file'],$conf['file.']), trim($this->getAltParam($conf, false)));
		if ($conf['linkWrap'])	{
			$theValue = $this->linkWrap($theValue,$conf['linkWrap']);
		}
		return $this->wrap($theValue,$conf['wrap']);
	}

	/**
	 * Rendering the cObject, IMAGE
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=353&cHash=440681ea56
	 * @see cImage()
	 */
	function IMAGE($conf)	{
		$content='';
		if ($this->checkIf($conf['if.']))	{
			$theValue = $this->cImage($conf['file'],$conf);
			if ($conf['stdWrap.'])	{
				$theValue = $this->stdWrap($theValue,$conf['stdWrap.']);
			}
			return $theValue;
		}
	}

	/**
	 * Rendering the cObject, IMG_RESOURCE
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=354&cHash=46f9299706
	 * @see getImgResource()
	 */
	function IMG_RESOURCE($conf)	{
		$GLOBALS['TSFE']->lastImgResourceInfo = $this->getImgResource($conf['file'],$conf['file.']);
		return $this->stdWrap($GLOBALS['TSFE']->lastImgResourceInfo[3],$conf['stdWrap.']);
	}

	/**
	 * Rendering the cObject, IMGTEXT
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=363&cHash=cf2969bce1
	 */
	function IMGTEXT($conf) {
		$content='';
		if (is_array($conf['text.']))	{
			$content.= $this->stdWrap($this->cObjGet($conf['text.'],'text.'),$conf['text.']);	// this gets the surrounding content
		}
		$imgList=trim($this->stdWrap($conf['imgList'],$conf['imgList.']));	// gets images
		if ($imgList)	{
			$imgs = t3lib_div::trimExplode(',',$imgList);
			$imgStart = intval($this->stdWrap($conf['imgStart'],$conf['imgStart.']));

			$imgCount= count($imgs)-$imgStart;

			$imgMax = intval($this->stdWrap($conf['imgMax'],$conf['imgMax.']));
			if ($imgMax)	{
				$imgCount = t3lib_div::intInRange($imgCount,0,$imgMax);	// reduces the number of images.
			}

			$imgPath = $this->stdWrap($conf['imgPath'],$conf['imgPath.']);

				// initialisation
			$caption='';
			$captionArray = array();
			if (!$conf['captionSplit'] && !$conf['imageTextSplit'] && is_array($conf['caption.']))	{
				$caption = $this->stdWrap($this->cObjGet($conf['caption.'], 'caption.'),$conf['caption.']);	// global caption, no splitting
			}
			if ($conf['captionSplit'] && $conf['captionSplit.']['cObject'])	{
				$legacyCaptionSplit = 1;
				$capSplit = $this->stdWrap($conf['captionSplit.']['token'], $conf['captionSplit.']['token.']);
				if (!$capSplit) {$capSplit=LF;}
				$captionArray = explode($capSplit, $this->cObjGetSingle($conf['captionSplit.']['cObject'], $conf['captionSplit.']['cObject.'], 'captionSplit.cObject'));
				foreach ($captionArray as $ca_key => $ca_val) {
					$captionArray[$ca_key] = $this->stdWrap(trim($captionArray[$ca_key]), $conf['captionSplit.']['stdWrap.']);
				}
			}

			$tablecode='';
			$position=$this->stdWrap($conf['textPos'],$conf['textPos.']);

			$tmppos = $position&7;
			$contentPosition = $position&24;
			$align = $this->align[$tmppos];
			$cap = ($caption)?1:0;
			$txtMarg = intval($this->stdWrap($conf['textMargin'],$conf['textMargin.']));
			if (!$conf['textMargin_outOfText'] && $contentPosition<16)	{
				$txtMarg=0;
			}

			$cols = intval($this->stdWrap($conf['cols'],$conf['cols.']));
			$rows = intval($this->stdWrap($conf['rows'],$conf['rows.']));
			$colspacing = intval($this->stdWrap($conf['colSpace'],$conf['colSpace.']));
			$rowspacing = intval($this->stdWrap($conf['rowSpace'],$conf['rowSpace.']));

			$border = intval($this->stdWrap($conf['border'],$conf['border.'])) ? 1:0;
			$borderColor = $this->stdWrap($conf['borderCol'],$conf['borderCol.']);
			$borderThickness = intval($this->stdWrap($conf['borderThick'],$conf['borderThick.']));

			$borderColor=$borderColor?$borderColor:'black';
			$borderThickness=$borderThickness?$borderThickness:1;

			$caption_align = $this->stdWrap($conf['captionAlign'],$conf['captionAlign.']);
			if (!$caption_align) {
				$caption_align = $align;
			}
				// generate cols
			$colCount = ($cols > 1) ? $cols : 1;
			if ($colCount > $imgCount)	{$colCount = $imgCount;}
			$rowCount = ($colCount > 1) ? ceil($imgCount / $colCount) : $imgCount;
				// generate rows
			if ($rows>1)  {
				$rowCount = $rows;
				if ($rowCount > $imgCount)	{$rowCount = $imgCount;}
				$colCount = ($rowCount>1) ? ceil($imgCount / $rowCount) : $imgCount;
			}

				// max Width
			$colRelations = trim($this->stdWrap($conf['colRelations'],$conf['colRelations.']));
			$maxW = intval($this->stdWrap($conf['maxW'],$conf['maxW.']));

			$maxWInText = intval($this->stdWrap($conf['maxWInText'],$conf['maxWInText.']));
			if (!$maxWInText)	{	// If maxWInText is not set, it's calculated to the 50 % of the max...
				$maxWInText = round($maxW/2);
			}

			if ($maxWInText && $contentPosition>=16)	{	// inText
				$maxW = $maxWInText;
			}

			if ($maxW && $colCount > 0) {	// If there is a max width and if colCount is greater than  column
/*				debug($border*$borderThickness*2);
				debug($maxW);
				debug($colspacing);
				debug(($maxW-$colspacing*($colCount-1)-$colCount*$border*$borderThickness*2));
				*/
				$maxW = ceil(($maxW-$colspacing*($colCount-1)-$colCount*$border*$borderThickness*2)/$colCount);
			}
				// create the relation between rows
			$colMaxW = Array();
			if ($colRelations)	{
				$rel_parts = explode(':',$colRelations);
				$rel_total = 0;
				for ($a=0;$a<$colCount;$a++)	{
					$rel_parts[$a] = intval($rel_parts[$a]);
					$rel_total+= $rel_parts[$a];
				}
				if ($rel_total)	{
					for ($a=0;$a<$colCount;$a++)	{
						$colMaxW[$a] = round(($maxW*$colCount)/$rel_total*$rel_parts[$a]);
					}
					if (min($colMaxW)<=0 || max($rel_parts)/min($rel_parts)>10)	{		// The difference in size between the largest and smalles must be within a factor of ten.
						$colMaxW = Array();
					}
				}
			}
			$image_compression = intval($this->stdWrap($conf['image_compression'],$conf['image_compression.']));
			$image_effects = intval($this->stdWrap($conf['image_effects'],$conf['image_effects.']));
			$image_frames = intval($this->stdWrap($conf['image_frames.']['key'],$conf['image_frames.']['key.']));

				// fetches pictures
			$splitArr=array();
			$splitArr['imgObjNum']=$conf['imgObjNum'];
			$splitArr = $GLOBALS['TSFE']->tmpl->splitConfArray($splitArr,$imgCount);

				// EqualHeight
			$equalHeight = intval($this->stdWrap($conf['equalH'],$conf['equalH.']));
			if ($equalHeight)	{	// Initiate gifbuilder object in order to get dimensions AND calculate the imageWidth's
				$gifCreator = t3lib_div::makeInstance('tslib_gifbuilder');
				$gifCreator->init();
				$relations = Array();
				$relations_cols = Array();
				$totalMaxW = $maxW*$colCount;
				for($a=0;$a<$imgCount;$a++)	{
					$imgKey = $a+$imgStart;
					$imgInfo = $gifCreator->getImageDimensions($imgPath.$imgs[$imgKey]);
					$relations[$a] = $imgInfo[1] / $equalHeight;	// relationship between the original height and the wished height
					if ($relations[$a])	{	// if relations is zero, then the addition of this value is omitted as the image is not expected to display because of some error.
						$relations_cols[floor($a/$colCount)] += $imgInfo[0]/$relations[$a];	// counts the total width of the row with the new height taken into consideration.
					}
				}
			}

			$imageRowsFinalWidths = Array();	// contains the width of every image row
			$imageRowsMaxHeights = Array();
			$imgsTag=array();
			$origImages=array();
			for($a=0;$a<$imgCount;$a++)	{
				$GLOBALS['TSFE']->register['IMAGE_NUM'] = $a;
				$GLOBALS['TSFE']->register['IMAGE_NUM_CURRENT'] = $a;

				$imgKey = $a+$imgStart;
				$totalImagePath = $imgPath.$imgs[$imgKey];
				$this->data[$this->currentValKey] = $totalImagePath;
				$imgObjNum = intval($splitArr[$a]['imgObjNum']);
				$imgConf = $conf[$imgObjNum.'.'];

				if ($equalHeight)	{
					$scale = 1;
					if ($totalMaxW)	{
						$rowTotalMaxW = $relations_cols[floor($a/$colCount)];
						if ($rowTotalMaxW > $totalMaxW)	{
							$scale = $rowTotalMaxW / $totalMaxW;
						}
					}
						// transfer info to the imageObject. Please note, that
					$imgConf['file.']['height'] = round($equalHeight/$scale);

					unset($imgConf['file.']['width']);
					unset($imgConf['file.']['maxW']);
					unset($imgConf['file.']['maxH']);
					unset($imgConf['file.']['minW']);
					unset($imgConf['file.']['minH']);
					unset($imgConf['file.']['width.']);
					unset($imgConf['file.']['maxW.']);
					unset($imgConf['file.']['maxH.']);
					unset($imgConf['file.']['minW.']);
					unset($imgConf['file.']['minH.']);
					$maxW = 0;	// setting this to zero, so that it doesn't disturb
				}

				if ($maxW) {
					if (count($colMaxW))	{
						$imgConf['file.']['maxW'] = $colMaxW[($a%$colCount)];
					} else {
						$imgConf['file.']['maxW'] = $maxW;
					}
				}

					// Image Object supplied:
				if (is_array($imgConf)) {
					if ($this->image_effects[$image_effects])	{
						$imgConf['file.']['params'].= ' '.$this->image_effects[$image_effects];
					}
					if ($image_frames)	{
						if (is_array($conf['image_frames.'][$image_frames.'.']))	{
							$imgConf['file.']['m.'] = $conf['image_frames.'][$image_frames.'.'];
						}
					}
					if ($image_compression && $imgConf['file']!='GIFBUILDER')	{
						if ($image_compression==1)	{
							$tempImport = $imgConf['file.']['import'];
							$tempImport_dot = $imgConf['file.']['import.'];
							unset($imgConf['file.']);
							$imgConf['file.']['import'] = $tempImport;
							$imgConf['file.']['import.'] = $tempImport_dot;
						} elseif (isset($this->image_compression[$image_compression])) {
							$imgConf['file.']['params'].= ' '.$this->image_compression[$image_compression]['params'];
							$imgConf['file.']['ext'] = $this->image_compression[$image_compression]['ext'];
							unset($imgConf['file.']['ext.']);
						}
					}

						// "alt", "title" and "longdesc" attributes:
					if (!strlen($imgConf['altText']) && !is_array($imgConf['altText.'])) {
						$imgConf['altText'] = $conf['altText'];
						$imgConf['altText.'] = $conf['altText.'];
					}
					if (!strlen($imgConf['titleText']) && !is_array($imgConf['titleText.'])) {
						$imgConf['titleText'] = $conf['titleText'];
						$imgConf['titleText.'] = $conf['titleText.'];
					}
					if (!strlen($imgConf['longdescURL']) && !is_array($imgConf['longdescURL.'])) {
						$imgConf['longdescURL'] = $conf['longdescURL'];
						$imgConf['longdescURL.'] = $conf['longdescURL.'];
					}
				} else {
					$imgConf = array(
						'altText' => $conf['altText'],
						'titleText' => $conf['titleText'],
						'longdescURL' => $conf['longdescURL'],
						'file' => $totalImagePath
					);
				}

				$imgsTag[$imgKey] = $this->IMAGE($imgConf);

					// Store the original filepath
				$origImages[$imgKey]=$GLOBALS['TSFE']->lastImageInfo;

				$imageRowsFinalWidths[floor($a/$colCount)] += $GLOBALS['TSFE']->lastImageInfo[0];
				if ($GLOBALS['TSFE']->lastImageInfo[1]>$imageRowsMaxHeights[floor($a/$colCount)])	{
					$imageRowsMaxHeights[floor($a/$colCount)] = $GLOBALS['TSFE']->lastImageInfo[1];
				}
			}
				// calculating the tableWidth:
				// TableWidth problems: It creates problems if the pictures are NOT as wide as the tableWidth.
			$tableWidth = max($imageRowsFinalWidths)+ $colspacing*($colCount-1) + $colCount*$border*$borderThickness*2;

				// make table for pictures
			$index=$imgStart;

			$noRows = $this->stdWrap($conf['noRows'],$conf['noRows.']);
			$noCols = $this->stdWrap($conf['noCols'],$conf['noCols.']);
			if ($noRows) {$noCols=0;}	// noRows overrides noCols. They cannot exist at the same time.
			if ($equalHeight) {
				$noCols=1;
				$noRows=0;
			}

			$rowCount_temp=1;
			$colCount_temp=$colCount;
			if ($noRows)	{
				$rowCount_temp = $rowCount;
				$rowCount=1;
			}
			if ($noCols)	{
				$colCount=1;
			}
				// col- and rowspans calculated
			$colspan = (($colspacing) ? $colCount*2-1 : $colCount);
			$rowspan = (($rowspacing) ? $rowCount*2-1 : $rowCount) + $cap;


				// Edit icons:
			$editIconsHTML = $conf['editIcons']&&$GLOBALS['TSFE']->beUserLogin ? $this->editIcons('',$conf['editIcons'],$conf['editIcons.']) : '';

				// strech out table:
			$tablecode='';
			$flag=0;
			if ($conf['noStretchAndMarginCells']!=1)	{
				$tablecode.='<tr>';
				if ($txtMarg && $align=='right')	{	// If right aligned, the textborder is added on the right side
					$tablecode.='<td rowspan="'.($rowspan+1).'" valign="top"><img src="'.$GLOBALS['TSFE']->absRefPrefix.'clear.gif" width="'.$txtMarg.'" height="1" alt="" title="" />'.($editIconsHTML?'<br />'.$editIconsHTML:'').'</td>';
					$editIconsHTML='';
					$flag=1;
				}
				$tablecode.='<td colspan="'.$colspan.'"><img src="'.$GLOBALS['TSFE']->absRefPrefix.'clear.gif" width="'.$tableWidth.'" height="1" alt="" /></td>';
				if ($txtMarg && $align=='left')	{	// If left aligned, the textborder is added on the left side
					$tablecode.='<td rowspan="'.($rowspan+1).'" valign="top"><img src="'.$GLOBALS['TSFE']->absRefPrefix.'clear.gif" width="'.$txtMarg.'" height="1" alt="" title="" />'.($editIconsHTML?'<br />'.$editIconsHTML:'').'</td>';
					$editIconsHTML='';
					$flag=1;
				}
				if ($flag) $tableWidth+=$txtMarg+1;
	//			$tableWidth=0;
				$tablecode.='</tr>';
			}

				// draw table
			for ($c=0;$c<$rowCount;$c++) {	// Looping through rows. If 'noRows' is set, this is '1 time', but $rowCount_temp will hold the actual number of rows!
				if ($c && $rowspacing)	{		// If this is NOT the first time in the loop AND if space is required, a row-spacer is added. In case of "noRows" rowspacing is done further down.
					$tablecode.='<tr><td colspan="'.$colspan.'"><img src="'.$GLOBALS['TSFE']->absRefPrefix.'clear.gif" width="1" height="'.$rowspacing.'"'.$this->getBorderAttr(' border="0"').' alt="" title="" /></td></tr>';
				}
				$tablecode.='<tr>';	// starting row
				for ($b=0; $b<$colCount_temp; $b++)	{	// Looping through the columns
					if ($b && $colspacing)	{		// If this is NOT the first iteration AND if column space is required. In case of "noCols", the space is done without a separate cell.
						if (!$noCols)	{
							$tablecode.='<td><img src="'.$GLOBALS['TSFE']->absRefPrefix.'clear.gif" width="'.$colspacing.'" height="1"'.$this->getBorderAttr(' border="0"').' alt="" title="" /></td>';
						} else {
							$colSpacer='<img src="'.$GLOBALS['TSFE']->absRefPrefix.'clear.gif" width="'.($border?$colspacing-6:$colspacing).'" height="'.($imageRowsMaxHeights[$c]+($border?$borderThickness*2:0)).'"'.$this->getBorderAttr(' border="0"').' align="'.($border?'left':'top').'" alt="" title="" />';
							$colSpacer='<td valign="top">'.$colSpacer.'</td>';	// added 160301, needed for the new "noCols"-table...
							$tablecode.=$colSpacer;
						}
					}
					if (!$noCols || ($noCols && !$b))	{
						$tablecode.='<td valign="top">';	// starting the cell. If "noCols" this cell will hold all images in the row, otherwise only a single image.
						if ($noCols)	{$tablecode.='<table width="'.$imageRowsFinalWidths[$c].'" border="0" cellpadding="0" cellspacing="0"><tr>';}		// In case of "noCols" we must set the table-tag that surrounds the images in the row.
					}
					for ($a=0;$a<$rowCount_temp;$a++)	{	// Looping through the rows IF "noRows" is set. "noRows"  means that the rows of images is not rendered by physical table rows but images are all in one column and spaced apart with clear-gifs. This loop is only one time if "noRows" is not set.
						$GLOBALS['TSFE']->register['IMAGE_NUM'] = $imgIndex;	// register previous imgIndex
						$imgIndex = $index+$a*$colCount_temp;
						$GLOBALS['TSFE']->register['IMAGE_NUM_CURRENT'] = $imgIndex;
						if ($imgsTag[$imgIndex])	{
							if ($rowspacing && $noRows && $a) {		// Puts distance between the images IF "noRows" is set and this is the first iteration of the loop
								$tablecode.= '<img src="'.$GLOBALS['TSFE']->absRefPrefix.'clear.gif" width="1" height="'.$rowspacing.'" alt="" title="" /><br />';
							}
							if ($legacyCaptionSplit)	{
								$thisCaption = $captionArray[$imgIndex];
							} else if ($conf['captionSplit'] || $conf['imageTextSplit'])	{
								$thisCaption = $this->stdWrap($this->cObjGet($conf['caption.'], 'caption.'), $conf['caption.']);
							}
							$imageHTML = $imgsTag[$imgIndex].'<br />';
							$Talign = (!trim($thisCaption) && !$noRows) ? ' align="left"' : '';  // this is necessary if the tablerows are supposed to space properly together! "noRows" is excluded because else the images "layer" together.
							if ($border)	{$imageHTML='<table border="0" cellpadding="'.$borderThickness.'" cellspacing="0" bgcolor="'.$borderColor.'"'.$Talign.'><tr><td>'.$imageHTML.'</td></tr></table>';}
							$imageHTML.=$editIconsHTML;
							$editIconsHTML='';
							$imageHTML.=$thisCaption;	// Adds caption.
							if ($noCols)	{$imageHTML='<td valign="top">'.$imageHTML.'</td>';}		// If noCols, put in table cell.
							$tablecode.=$imageHTML;
						}
					}
					$index++;
					if (!$noCols || ($noCols && $b+1==$colCount_temp))	{
						if ($noCols)	{$tablecode.='</tr></table>';}	// In case of "noCols" we must finish the table that surrounds the images in the row.
						$tablecode.='</td>';	// Ending the cell. In case of "noCols" the cell holds all pictures!
					}
				}
				$tablecode.='</tr>';	// ending row
			}
			if ($c)	{
				switch ($contentPosition)	{
					case '0':	// above
					case '8':	// below
						switch ($align)        {	// These settings are needed for Firefox
							case 'center':
								$table_align = 'margin-left: auto; margin-right: auto';
							break;
							case 'right':
								$table_align = 'margin-left: auto; margin-right: 0px';
							break;
							default:	// Most of all: left
								$table_align = 'margin-left: 0px; margin-right: auto';
						}
						$table_align = 'style="'.$table_align.'"';
					break;
					case '16':	// in text
						$table_align = 'align="'.$align.'"';
					break;
					default:
						$table_align = '';
				}

					// Table-tag is inserted
				$tablecode = '<table'.($tableWidth?' width="'.$tableWidth.'"':'').' border="0" cellspacing="0" cellpadding="0" '.$table_align.' class="imgtext-table">'.$tablecode;
				if ($editIconsHTML)	{	// IF this value is not long since reset.
					$tablecode.='<tr><td colspan="'.$colspan.'">'.$editIconsHTML.'</td></tr>';
					$editIconsHTML='';
				}
				if ($cap)	{
					$tablecode.='<tr><td colspan="'.$colspan.'" align="'.$caption_align.'">'.$caption.'</td></tr>';
				}
				$tablecode.='</table>';
				if ($conf['tableStdWrap.'])	{$tablecode=$this->stdWrap($tablecode,$conf['tableStdWrap.']);}
			}

			$spaceBelowAbove = intval($this->stdWrap($conf['spaceBelowAbove'],$conf['spaceBelowAbove.']));
			switch ($contentPosition)	{
				case '0':	// above
					$output= '<div style="text-align:'.$align.';">'.$tablecode.'</div>'.$this->wrapSpace($content, $spaceBelowAbove.'|0');
				break;
				case '8':	// below
					$output= $this->wrapSpace($content, '0|'.$spaceBelowAbove).'<div style="text-align:'.$align.';">'.$tablecode.'</div>';
				break;
				case '16':	// in text
					$output= $tablecode.$content;
				break;
				case '24':	// in text, no wrap
					$theResult = '';
					$theResult.= '<table border="0" cellspacing="0" cellpadding="0" class="imgtext-nowrap"><tr>';
					if ($align=='right')	{
						$theResult.= '<td valign="top">'.$content.'</td><td valign="top">'.$tablecode.'</td>';
					} else {
						$theResult.= '<td valign="top">'.$tablecode.'</td><td valign="top">'.$content.'</td>';
					}
					$theResult.= '</tr></table>';
					$output= $theResult;
				break;
			}
		} else {
			$output= $content;
		}

		if ($conf['stdWrap.']) {
			$output = $this->stdWrap($output, $conf['stdWrap.']);
		}

		return $output;
	}

	/**
	 * Rendering the cObject, CONTENT
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=356&cHash=9f3b5c6ba2
	 */
	function CONTENT($conf)	{
		$theValue='';

		$originalRec = $GLOBALS['TSFE']->currentRecord;
		if ($originalRec)	{		// If the currentRecord is set, we register, that this record has invoked this function. It's should not be allowed to do this again then!!
			$GLOBALS['TSFE']->recordRegister[$originalRec]++;
		}

		$conf['table'] = trim($this->stdWrap($conf['table'], $conf['table.']));
		if ($conf['table']=='pages' || substr($conf['table'],0,3)=='tt_' || substr($conf['table'],0,3)=='fe_' || substr($conf['table'],0,3)=='tx_' || substr($conf['table'],0,4)=='ttx_' || substr($conf['table'],0,5)=='user_' || substr($conf['table'],0,7)=='static_')	{

			$renderObjName = $conf['renderObj'] ? $conf['renderObj'] : '<'.$conf['table'];
			$renderObjKey = $conf['renderObj'] ? 'renderObj' : '';
			$renderObjConf = $conf['renderObj.'];

			$slide = intval($conf['slide'])?intval($conf['slide']):0;
			$slideCollect = intval($conf['slide.']['collect'])?intval($conf['slide.']['collect']):0;
			$slideCollectReverse = intval($conf['slide.']['collectReverse'])?true:false;
			$slideCollectFuzzy = $slideCollect?(intval($conf['slide.']['collectFuzzy'])?true:false):true;
			$again = false;

			do {
				$res = $this->exec_getQuery($conf['table'],$conf['select.']);
				if ($error = $GLOBALS['TYPO3_DB']->sql_error()) {
					$GLOBALS['TT']->setTSlogMessage($error,3);
				} else {
					$this->currentRecordTotal = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
					$GLOBALS['TT']->setTSlogMessage('NUMROWS: '.$GLOBALS['TYPO3_DB']->sql_num_rows($res));
					$cObj =t3lib_div::makeInstance('tslib_cObj');
					$cObj->setParent($this->data,$this->currentRecord);
					$this->currentRecordNumber=0;
					$cobjValue = '';
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

							// Versioning preview:
						$GLOBALS['TSFE']->sys_page->versionOL($conf['table'],$row,TRUE);

							// Language overlay:
						if (is_array($row) && $GLOBALS['TSFE']->sys_language_contentOL) {
							$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay($conf['table'],$row,$GLOBALS['TSFE']->sys_language_content,$GLOBALS['TSFE']->sys_language_contentOL);
						}

						if (is_array($row)) { // Might be unset in the sys_language_contentOL
							if (!$GLOBALS['TSFE']->recordRegister[$conf['table'].':'.$row['uid']]) {
								$this->currentRecordNumber++;
								$cObj->parentRecordNumber = $this->currentRecordNumber;
								$GLOBALS['TSFE']->currentRecord = $conf['table'].':'.$row['uid'];
								$this->lastChanged($row['tstamp']);
								$cObj->start($row,$conf['table']);
								$tmpValue = $cObj->cObjGetSingle($renderObjName, $renderObjConf, $renderObjKey);
								$cobjValue .= $tmpValue;
							}
						}
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($res);
				}
				if ($slideCollectReverse) {
					$theValue = $cobjValue.$theValue;
				} else {
					$theValue .= $cobjValue;
				}
				if ($slideCollect>0) {
					$slideCollect--;
				}
				if ($slide) {
					if ($slide>0) {
						$slide--;
					}
					$conf['select.']['pidInList'] = $this->getSlidePids($conf['select.']['pidInList'], $conf['select.']['pidInList.']);
					$again = strlen($conf['select.']['pidInList'])?true:false;
				}
			} while ($again&&(($slide&&!strlen($tmpValue)&&$slideCollectFuzzy)||($slide&&$slideCollect)));
		}

		$theValue = $this->wrap($theValue,$conf['wrap']);
		if ($conf['stdWrap.']) $theValue = $this->stdWrap($theValue,$conf['stdWrap.']);

		$GLOBALS['TSFE']->currentRecord = $originalRec;	// Restore
		return $theValue;
	}

	/**
	 * Rendering the cObject, RECORDS
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=357&cHash=303e959472
	 */
	function RECORDS($conf)	{
		$theValue='';

		$originalRec = $GLOBALS['TSFE']->currentRecord;
		if ($originalRec)	{		// If the currentRecord is set, we register, that this record has invoked this function. It's should not be allowed to do this again then!!
			$GLOBALS['TSFE']->recordRegister[$originalRec]++;
		}

		$conf['source'] = $this->stdWrap($conf['source'],$conf['source.']);
		if ($conf['tables'] && $conf['source']) {
			$allowedTables = $conf['tables'];
			if (is_array($conf['conf.']))	{
				foreach ($conf['conf.'] as $k => $v) {
					if (substr($k,-1)!='.')		$allowedTables.=','.$k;
				}
			}

			$loadDB = t3lib_div::makeInstance('FE_loadDBGroup');
			$loadDB->start($conf['source'], $allowedTables);
			foreach ($loadDB->tableArray as $table => $v) {
				if (is_array($GLOBALS['TCA'][$table]))	{
					$loadDB->additionalWhere[$table]=$this->enableFields($table);
				}
			}
			$loadDB->getFromDB();

			reset($loadDB->itemArray);
			$data = $loadDB->results;

			$cObj =t3lib_div::makeInstance('tslib_cObj');
			$cObj->setParent($this->data,$this->currentRecord);
			$this->currentRecordNumber=0;
			$this->currentRecordTotal = count($loadDB->itemArray);
			foreach ($loadDB->itemArray as $val) {
				$row = $data[$val['table']][$val['id']];

					// Versioning preview:
				$GLOBALS['TSFE']->sys_page->versionOL($val['table'],$row);

					// Language overlay:
				if (is_array($row) && $GLOBALS['TSFE']->sys_language_contentOL)	{
					$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay($val['table'],$row,$GLOBALS['TSFE']->sys_language_content,$GLOBALS['TSFE']->sys_language_contentOL);
				}

				if (is_array($row))	{	// Might be unset in the content overlay things...
					if (!$conf['dontCheckPid'])	{
						$row = $this->checkPid($row['pid']) ? $row : '';
					}
					if ($row && !$GLOBALS['TSFE']->recordRegister[$val['table'].':'.$val['id']])	{
						$renderObjName = $conf['conf.'][$val['table']] ? $conf['conf.'][$val['table']] : '<'.$val['table'];
						$renderObjKey = $conf['conf.'][$val['table']] ? 'conf.'.$val['table'] : '';
						$renderObjConf = $conf['conf.'][$val['table'].'.'];
						$this->currentRecordNumber++;
						$cObj->parentRecordNumber=$this->currentRecordNumber;
						$GLOBALS['TSFE']->currentRecord = $val['table'].':'.$val['id'];
						$this->lastChanged($row['tstamp']);
						$cObj->start($row,$val['table']);
						$tmpValue = $cObj->cObjGetSingle($renderObjName, $renderObjConf, $renderObjKey);
						$theValue .= $tmpValue;
					}# else debug($GLOBALS['TSFE']->recordRegister,'RECORDS');
				}
			}
		}
		if ($conf['wrap'])	$theValue = $this->wrap($theValue,$conf['wrap']);
		if ($conf['stdWrap.'])	$theValue = $this->stdWrap($theValue,$conf['stdWrap.']);

		$GLOBALS['TSFE']->currentRecord = $originalRec;	// Restore
		return $theValue;
	}

	/**
	 * Rendering the cObject, HMENU
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=358&cHash=5400c1c06a
	 */
	function HMENU($conf)	{
		$content='';
		if ($this->checkIf($conf['if.']))	{
			$cls = strtolower($conf[1]);
			if (t3lib_div::inList($GLOBALS['TSFE']->tmpl->menuclasses,$cls))	{
				if ($conf['special.']['value.'])	{
					$conf['special.']['value']  = $this->stdWrap($conf['special.']['value'], $conf['special.']['value.']);
				}
				$GLOBALS['TSFE']->register['count_HMENU']++;
				$GLOBALS['TSFE']->register['count_HMENU_MENUOBJ']=0;
				$GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMid']=array();
				$GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMparentId']=array();

				$menu = t3lib_div::makeInstance('tslib_'.$cls);
				$menu->parent_cObj = $this;
				$menu->start($GLOBALS['TSFE']->tmpl, $GLOBALS['TSFE']->sys_page, '', $conf, 1);
				$menu->makeMenu();
				$content.=$menu->writeMenu();
			}
			if ($conf['wrap']) 		$content=$this->wrap($content, $conf['wrap']);
			if ($conf['stdWrap.'])	$content = $this->stdWrap($content, $conf['stdWrap.']);
		}
		return $content;
	}

	/**
	 * Rendering the cObject, CTABLE
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=359&cHash=2e0065b4e7
	 */
	function CTABLE ($conf)	{
		$controlTable = t3lib_div::makeInstance('tslib_controlTable');
			if ($conf['tableParams'])	{
			$controlTable->tableParams = $conf['tableParams'];
		}
			// loads the pagecontent
		$controlTable->contentW = $conf['cWidth'];
			// loads the menues if any
		if (is_array($conf['c.']))	{
			$controlTable->content = $this->cObjGet($conf['c.'],'c.');
			$controlTable->contentTDparams = isset($conf['c.']['TDParams']) ? $conf['c.']['TDParams'] : 'valign="top"';
		}
		if (is_array($conf['lm.']))	{
			$controlTable->lm = $this->cObjGet($conf['lm.'],'lm.');
			$controlTable->lmTDparams = isset($conf['lm.']['TDParams']) ? $conf['lm.']['TDParams'] : 'valign="top"';
		}
		if (is_array($conf['tm.']))	{
			$controlTable->tm = $this->cObjGet($conf['tm.'],'tm.');
			$controlTable->tmTDparams = isset($conf['tm.']['TDParams']) ? $conf['tm.']['TDParams'] : 'valign="top"';
		}
		if (is_array($conf['rm.']))	{
			$controlTable->rm = $this->cObjGet($conf['rm.'],'rm.');
			$controlTable->rmTDparams = isset($conf['rm.']['TDParams']) ? $conf['rm.']['TDParams'] : 'valign="top"';
		}
		if (is_array($conf['bm.']))	{
			$controlTable->bm = $this->cObjGet($conf['bm.'],'bm.');
			$controlTable->bmTDparams = isset($conf['bm.']['TDParams']) ? $conf['bm.']['TDParams'] : 'valign="top"';
		}
		return $controlTable->start($conf['offset'],$conf['cMargins']);
	}

	/**
	 * Rendering the cObject, OTABLE
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=360&cHash=02c9552d38
	 */
	function OTABLE ($conf)	{
		$controlTable = t3lib_div::makeInstance('tslib_tableOffset');
		if ($conf['tableParams'])	{
			$controlTable->tableParams = $conf['tableParams'];
		}
		return $controlTable->start($this->cObjGet($conf),$conf['offset']);
	}

	/**
	 * Rendering the cObject, COLUMNS
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=361&cHash=7e4e228cad
	 */
	function COLUMNS ($conf)	{
		$content='';
		if (is_array($conf) && $this->checkIf($conf['if.']))	{
			$tdRowCount=0;
			$tableParams = $conf['tableParams'] ? ' '.$conf['tableParams'] : ' border="0" cellspacing="0" cellpadding="0"';
			$TDparams = $conf['TDparams'] ? ' '.$conf['TDparams']:' valign="top"';
			$rows = t3lib_div::intInRange($conf['rows'],2,20);
			$totalWidth = intval($conf['totalWidth']);
			$columnWidth=0;

			$totalGapWidth=0;
			$gapData = Array(
				'gapWidth' => $this->stdWrap($conf['gapWidth'],$conf['gapWidth.']),
				'gapBgCol' => $this->stdWrap($conf['gapBgCol'],$conf['gapBgCol.']),
				'gapLineThickness' => $this->stdWrap($conf['gapLineThickness'],$conf['gapLineThickness.']),
				'gapLineCol' => $this->stdWrap($conf['gapLineCol'],$conf['gapLineCol.'])
			);
			$gapData = $GLOBALS['TSFE']->tmpl->splitConfArray($gapData,$rows-1);
			foreach ($gapData as $val) {
				$totalGapWidth+=intval($val['gapWidth']);
			}

			if ($totalWidth)	{
				$columnWidth = ceil(($totalWidth-$totalGapWidth)/$rows);
				$TDparams.=' width="'.$columnWidth.'"';
				$tableParams.=' width="'.$totalWidth.'"';
			} else {
				$TDparams.=' width="'.floor(100/$rows).'%"';
				$tableParams.=' width="100%"';
			}

			for ($a=1;$a<=$rows;$a++)	{
				$tdRowCount++;
				$content.='<td'.$TDparams.'>';
				$content.=$this->cObjGetSingle($conf[$a],$conf[$a.'.'], $a);
				$content.='</td>';
				if ($a < $rows)	{
					$gapConf = $gapData[($a-1)];
					$gapWidth = intval($gapConf['gapWidth']);
					if ($gapWidth)	{
						$tdPar = $gapConf['gapBgCol'] ? ' bgcolor="'.$gapConf['gapBgCol'].'"' : '';
						$gapLine = intval($gapConf['gapLineThickness']);
						if ($gapLine)	{
							$gapSurround = t3lib_div::intInRange(($gapWidth-$gapLine)/2, 1, 1000);
								// right gap
							$content.='<td'.$tdPar.'><img src="'.$GLOBALS['TSFE']->absRefPrefix.'clear.gif" width="'.$gapSurround.'" height="1" alt="" title="" /></td>';
							$tdRowCount++;
								// line:
							$GtdPar = $gapConf['gapLineCol'] ? ' bgcolor="'.$gapConf['gapLineCol'].'"' : ' bgcolor="black"';
							$content.='<td'.$GtdPar.'><img src="'.$GLOBALS['TSFE']->absRefPrefix.'clear.gif" width="'.$gapLine.'" height="1" alt="" title="" /></td>';
							$tdRowCount++;
								// left gap
							$content.='<td'.$tdPar.'><img src="'.$GLOBALS['TSFE']->absRefPrefix.'clear.gif" width="'.$gapSurround.'" height="1" alt="" title="" /></td>';
							$tdRowCount++;
						} else {
							$content.='<td'.$tdPar.'><img src="'.$GLOBALS['TSFE']->absRefPrefix.'clear.gif" width="'.$gapWidth.'" height="1" alt="" title="" /></td>';
							$tdRowCount++;
						}
					}
				}
			}
			$content = '<tr>'.$content.'</tr>';
			$content = '<table'.$tableParams.'>'.$content.'</table>';
			$content.= $this->cObjGetSingle($conf['after'],$conf['after.'], 'after');
			if ($conf['stdWrap.'])	{
				$content = $this->stdWrap($content,$conf['stdWrap.']);
			}
		}
		return $content;
	}

	/**
	 * Rendering the cObject, HRULER
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=362&cHash=2a462aa084
	 */
	function HRULER ($conf)	{
		$lineThickness = t3lib_div::intInRange($this->stdWrap($conf['lineThickness'],$conf['lineThickness.']),1,50);
		$lineColor = $conf['lineColor'] ? $conf['lineColor'] : 'black';
		$spaceBefore = intval($conf['spaceLeft']);
		$spaceAfter = intval($conf['spaceRight']);
		$tableWidth = $conf['tableWidth'] ? $conf['tableWidth'] : '99%';
		$content='';

		$content.='<table border="0" cellspacing="0" cellpadding="0" width="'.htmlspecialchars($tableWidth).'" summary=""><tr>';
		if ($spaceBefore)	{$content.='<td width="1"><img src="'.$GLOBALS['TSFE']->absRefPrefix.'clear.gif" width="'.$spaceBefore.'" height="1" alt="" title="" /></td>'; }
		$content.='<td bgcolor="'.$lineColor.'"><img src="'.$GLOBALS['TSFE']->absRefPrefix.'clear.gif" width="1" height="'.$lineThickness.'" alt="" title="" /></td>';
		if ($spaceAfter)	{$content.='<td width="1"><img src="'.$GLOBALS['TSFE']->absRefPrefix.'clear.gif" width="'.$spaceAfter.'" height="1" alt="" title="" /></td>'; }
		$content.='</tr></table>';

		$content = $this->stdWrap($content, $conf['stdWrap.']);
		return $content;
	}

	/**
	 * Rendering the cObject, CASE
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=364&cHash=cffedd09e3
	 */
	function CASEFUNC ($conf){
		$content='';
		if ($this->checkIf($conf['if.']))	{
			if ($conf['setCurrent'] || $conf['setCurrent.']){$this->data[$this->currentValKey] = $this->stdWrap($conf['setCurrent'], $conf['setCurrent.']);}
	 		$key = $this->stdWrap($conf['key'],$conf['key.']);
	 		$key = strlen($conf[$key]) ? $key : 'default';
	 		$name = $conf[$key];
	 		$theValue = $this->cObjGetSingle($name,$conf[$key.'.'], $key);
	 		if ($conf['stdWrap.'])	{
	 			$theValue = $this->stdWrap($theValue,$conf['stdWrap.']);
	 		}
	 		return $theValue;
		}
	}

	/**
	 * Rendering the cObject, LOAD_REGISTER and RESTORE_REGISTER
	 * NOTICE: This cObject does NOT return any content since it just sets internal data based on the TypoScript properties.
	 *
	 * @param	array		Array of TypoScript properties
	 * @param	string		If "RESTORE_REGISTER" then the cObject rendered is "RESTORE_REGISTER", otherwise "LOAD_REGISTER"
	 * @return	string		Empty string (the cObject only sets internal data!)
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=365&cHash=4935524e2e
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=366&cHash=4f9485e8cc
	 */
	function LOAD_REGISTER($conf,$name)	{
		if ($name=='RESTORE_REGISTER')	{
			$GLOBALS['TSFE']->register = array_pop($GLOBALS['TSFE']->registerStack);
		} else {
			array_push($GLOBALS['TSFE']->registerStack,$GLOBALS['TSFE']->register);
			if (is_array($conf))	{
				foreach ($conf as $theKey => $theValue) {
					if (!strstr($theKey,'.') || !isset($conf[substr($theKey,0,-1)]))	{		// Only if 1) the property is set but not the value itself, 2) the value and/or any property
						if (strstr($theKey,'.'))	{
							$theKey = substr($theKey,0,-1);
						}
						$GLOBALS['TSFE']->register[$theKey] = $this->stdWrap($conf[$theKey],$conf[$theKey.'.']);
					}
				}
			}
		}
		return '';
	}

	/**
	 * Rendering the cObject, FORM
	 *
	 * Note on $formData:
	 * In the optional $formData array each entry represents a line in the ordinary setup.
	 * In those entries each entry (0,1,2...) represents a space normally divided by the '|' line.
	 *
	 * $formData [] = array('Name:', 'name=input, 25 ', 'Default value....');
	 * $formData [] = array('Email:', 'email=input, 25 ', 'Default value for email....');
	 *
	 * - corresponds to the $conf['data'] value being :
	 * Name:|name=input, 25 |Default value....||Email:|email=input, 25 |Default value for email....
	 *
	 * If $formData is an array the value of $conf['data'] is ignored.
	 *
	 * @param	array		Array of TypoScript properties
	 * @param	array		Alternative formdata overriding whatever comes from TypoScript
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=367&cHash=bbc518d930
	 */
	function FORM($conf,$formData='') {
		$content='';
		if (is_array($formData)) {
			$dataArr = $formData;
		} else {
			$data = $this->stdWrap($conf['data'],$conf['data.']);
				// Clearing dataArr
			$dataArr = array();
				// Getting the original config
			if (trim($data))	{
				$data = str_replace(LF,'||',$data);
				$dataArr = explode('||',$data);
			}
				// Adding the new dataArray config form:
			if (is_array($conf['dataArray.'])) {	// dataArray is supplied
				$sKeyArray = t3lib_TStemplate::sortedKeyList($conf['dataArray.'], TRUE);
				foreach ($sKeyArray as $theKey)	{
					$dAA = $conf['dataArray.'][$theKey.'.'];
					if (is_array($dAA))	{
						$temp = array();
						list($temp[0]) = explode('|',$dAA['label.'] ? $this->stdWrap($dAA['label'],$dAA['label.']) : $dAA['label']);
						list($temp[1]) = explode('|',$dAA['type']);
						if ($dAA['required']) {
							$temp[1] = '*'.$temp[1];
						}
						list($temp[2]) = explode('|',$dAA['value.'] ? $this->stdWrap($dAA['value'],$dAA['value.']) : $dAA['value']);
							// If value Array is set, then implode those values.
						if (is_array($dAA['valueArray.'])) {
							$temp_accum = array();
							foreach ($dAA['valueArray.'] as $dAKey_vA => $dAA_vA) {
								if (is_array($dAA_vA) && !strcmp(intval($dAKey_vA).'.',$dAKey_vA))	{
									$temp_vA=array();
									list($temp_vA[0])= explode('=',$dAA_vA['label.'] ? $this->stdWrap($dAA_vA['label'],$dAA_vA['label.']) : $dAA_vA['label']);
									if ($dAA_vA['selected'])	{$temp_vA[0]='*'.$temp_vA[0];}
									list($temp_vA[1])= explode(',',$dAA_vA['value']);
								}
								$temp_accum[] = implode('=',$temp_vA);
							}
							$temp[2] = implode(',',$temp_accum);
						}
						list($temp[3]) = explode('|',$dAA['specialEval.'] ? $this->stdWrap($dAA['specialEval'],$dAA['specialEval.']) : $dAA['specialEval']);

							// adding the form entry to the dataArray
						$dataArr[] = implode('|',$temp);
					}
				}
			}
		}

		$attachmentCounter = '';
		$hiddenfields = '';
		$fieldlist = Array();
		$propertyOverride = Array();
		$fieldname_hashArray = Array();
		$cc = 0;

		$xhtmlStrict = t3lib_div::inList('xhtml_strict,xhtml_11,xhtml_2',$GLOBALS['TSFE']->xhtmlDoctype);
			// Formname
		if ($conf['formName']) {
			$formname = $this->cleanFormName($conf['formName']);
		} else {
			$formname = $GLOBALS['TSFE']->uniqueHash();
			$formname = 'a'.$formname;	// form name has to start with a letter to reach XHTML compliance
		}

		if (isset($conf['fieldPrefix'])) {
			if ($conf['fieldPrefix']) {
				$prefix = $this->cleanFormName($conf['fieldPrefix']);
			} else {
				$prefix = '';
			}
		} else {
			$prefix = $formname;
		}

		foreach ($dataArr as $val)	{

			$cc++;
			$confData=Array();
			if (is_array($formData)) {
				$parts = $val;
				$val = 1;    // true...
			} else {
				$val = trim($val);
				$parts = explode('|',$val);
			}
			if ($val && strcspn($val,'#/')) {
					// label:
				$confData['label'] = trim($parts[0]);
					// field:
				$fParts = explode(',',$parts[1]);
				$fParts[0]=trim($fParts[0]);
				if (substr($fParts[0],0,1)=='*')	{
					$confData['required']=1;
					$fParts[0] = substr($fParts[0],1);
				}
				$typeParts = explode('=',$fParts[0]);
				$confData['type'] = trim(strtolower(end($typeParts)));
				if (count($typeParts)==1)	{
					$confData['fieldname'] = $this->cleanFormName($parts[0]);
					if (strtolower(preg_replace('/[^[:alnum:]]/','',$confData['fieldname']))=='email')	{$confData['fieldname']='email';}
						// Duplicate fieldnames resolved
					if (isset($fieldname_hashArray[md5($confData['fieldname'])]))	{
						$confData['fieldname'].='_'.$cc;
					}
					$fieldname_hashArray[md5($confData['fieldname'])]=$confData['fieldname'];
						// Attachment names...
					if ($confData['type']=='file')	{
						$confData['fieldname']='attachment'.$attachmentCounter;
						$attachmentCounter=intval($attachmentCounter)+1;
					}
				} else {
					$confData['fieldname'] = str_replace(' ','_',trim($typeParts[0]));
				}
				$fieldCode='';

				if ($conf['wrapFieldName'])	{
					$confData['fieldname'] = $this->wrap($confData['fieldname'],$conf['wrapFieldName']);
				}

					// Set field name as current:
				$this->setCurrentVal($confData['fieldname']);

					// Additional parameters
				if (trim($confData['type']))	{
					$addParams=trim($conf['params']);
					if (is_array($conf['params.']) && isset($conf['params.'][$confData['type']]))	{
						$addParams=trim($conf['params.'][$confData['type']]);
					}
					if (strcmp('',$addParams))	{ $addParams=' '.$addParams; }
				} else $addParams='';

				if ($conf['dontMd5FieldNames']) {
					$fName = $confData['fieldname'];
				} else {
					$fName = md5($confData['fieldname']);
				}

					// Accessibility: Set id = fieldname attribute:
				if ($conf['accessibility'] || $xhtmlStrict)	{
					$elementIdAttribute = ' id="'.$prefix.$fName.'"';
				} else {
					$elementIdAttribute = '';
				}

					// Create form field based on configuration/type:
				switch ($confData['type'])	{
					case 'textarea':
						$cols=trim($fParts[1]) ? intval($fParts[1]) : 20;
						$compWidth = doubleval($conf['compensateFieldWidth'] ? $conf['compensateFieldWidth'] : $GLOBALS['TSFE']->compensateFieldWidth);
						$compWidth = $compWidth ? $compWidth : 1;
						$cols = t3lib_div::intInRange($cols*$compWidth, 1, 120);

						$rows=trim($fParts[2]) ? t3lib_div::intInRange($fParts[2],1,30) : 5;
						$wrap=trim($fParts[3]);
						if ($conf['noWrapAttr'] || $wrap === 'disabled')	{
							$wrap='';
						} else {
							$wrap = $wrap ? ' wrap="'.$wrap.'"' : ' wrap="virtual"';
						}
						$default = $this->getFieldDefaultValue($conf['noValueInsert'], $confData['fieldname'], str_replace('\n',LF,trim($parts[2])));
						$fieldCode=sprintf('<textarea name="%s"%s cols="%s" rows="%s"%s%s>%s</textarea>',
							$confData['fieldname'], $elementIdAttribute, $cols, $rows, $wrap, $addParams, t3lib_div::formatForTextarea($default));
					break;
					case 'input':
					case 'password':
						$size=trim($fParts[1]) ? intval($fParts[1]) : 20;
						$compWidth = doubleval($conf['compensateFieldWidth'] ? $conf['compensateFieldWidth'] : $GLOBALS['TSFE']->compensateFieldWidth);
						$compWidth = $compWidth ? $compWidth : 1;
						$size = t3lib_div::intInRange($size*$compWidth, 1, 120);
						$default = $this->getFieldDefaultValue($conf['noValueInsert'], $confData['fieldname'], trim($parts[2]));

						if ($confData['type']=='password')	{
							$default='';
						}

						$max=trim($fParts[2]) ? ' maxlength="'.t3lib_div::intInRange($fParts[2],1,1000).'"' : "";
						$theType = $confData['type']=='input' ? 'text' : 'password';

						$fieldCode=sprintf('<input type="%s" name="%s"%s size="%s"%s value="%s"%s />',
							$theType, $confData['fieldname'], $elementIdAttribute, $size, $max, htmlspecialchars($default), $addParams);

					break;
					case 'file':
						$size=trim($fParts[1]) ? t3lib_div::intInRange($fParts[1],1,60) : 20;
						$fieldCode=sprintf('<input type="file" name="%s"%s size="%s"%s />',
							$confData['fieldname'], $elementIdAttribute, $size, $addParams);
					break;
					case 'check':
							// alternative default value:
						$default = $this->getFieldDefaultValue($conf['noValueInsert'], $confData['fieldname'], trim($parts[2]));
						$checked = $default ? ' checked="checked"' : '';
						$fieldCode=sprintf('<input type="checkbox" value="%s" name="%s"%s%s%s />',
							1, $confData['fieldname'], $elementIdAttribute, $checked, $addParams);
					break;
					case 'select':
						$option='';
						$valueParts = explode(',',$parts[2]);
							// size
						if (strtolower(trim($fParts[1]))=='auto')	{$fParts[1]=count($valueParts);}		// Auto size set here. Max 20
						$size=trim($fParts[1]) ? t3lib_div::intInRange($fParts[1],1,20) : 1;
							// multiple
						$multiple = strtolower(trim($fParts[2]))=='m' ? ' multiple="multiple"' : '';

						$items=array();		// Where the items will be
						$defaults=array(); //RTF
						$pCount = count($valueParts);
						for($a=0;$a<$pCount;$a++)	{
							$valueParts[$a]=trim($valueParts[$a]);
							if (substr($valueParts[$a],0,1)=='*')	{	// Finding default value
								$sel='selected';
								$valueParts[$a] = substr($valueParts[$a],1);
							} else $sel='';
								// Get value/label
							$subParts=explode('=',$valueParts[$a]);
							$subParts[1] = (isset($subParts[1])?trim($subParts[1]):trim($subParts[0]));		// Sets the value
							$items[] = $subParts;	// Adds the value/label pair to the items-array
							if ($sel) {$defaults[]=$subParts[1];}	// Sets the default value if value/label pair is marked as default.
						}
							// alternative default value:
						$default = $this->getFieldDefaultValue($conf['noValueInsert'], $confData['fieldname'], $defaults);
						if (!is_array($default)) {
							$defaults=array();
							$defaults[] = $default;
						} else $defaults=$default;
							// Create the select-box:
						$iCount = count($items);
						for($a=0;$a<$iCount;$a++)	{
							$option.='<option value="'.$items[$a][1].'"'.(in_array($items[$a][1],$defaults)?' selected="selected"':'').'>'.trim($items[$a][0]).'</option>'; //RTF
						}

						if ($multiple)	$confData['fieldname'].='[]';	// The fieldname must be prepended '[]' if multiple select. And the reason why it's prepended is, because the required-field list later must also have [] prepended.
						$fieldCode=sprintf('<select name="%s"%s size="%s"%s%s>%s</select>',
							$confData['fieldname'], $elementIdAttribute, $size, $multiple, $addParams, $option); //RTF
					break;
					case 'radio':
						$option='';

						$valueParts = explode(',',$parts[2]);
						$items=array();		// Where the items will be
						$default='';
						$pCount = count($valueParts);
						for($a=0;$a<$pCount;$a++)	{
							$valueParts[$a]=trim($valueParts[$a]);
							if (substr($valueParts[$a],0,1)=='*')	{
								$sel='checked';
								$valueParts[$a] = substr($valueParts[$a],1);
							} else $sel='';
								// Get value/label
							$subParts=explode('=',$valueParts[$a]);
							$subParts[1] = (isset($subParts[1])?trim($subParts[1]):trim($subParts[0]));		// Sets the value
							$items[] = $subParts;	// Adds the value/label pair to the items-array
							if ($sel) {$default=$subParts[1];}	// Sets the default value if value/label pair is marked as default.
						}
							// alternative default value:
						$default = $this->getFieldDefaultValue($conf['noValueInsert'], $confData['fieldname'], $default);
							// Create the select-box:
						$iCount = count($items);
						for($a=0;$a<$iCount;$a++)	{
							$radioId = $prefix.$fName.$this->cleanFormName($items[$a][0]);
							if ($conf['accessibility'])	{
								$radioLabelIdAttribute = ' id="'.$radioId.'"';
							} else {
								$radioLabelIdAttribute = '';
							}
							$option .= '<input type="radio" name="'.$confData['fieldname'].'"'.$radioLabelIdAttribute.' value="'.$items[$a][1].'"'.(!strcmp($items[$a][1],$default)?' checked="checked"':'').$addParams.' />';
							if ($conf['accessibility'])	{
								$option .= '<label for="'.$radioId.'">' . $this->stdWrap(trim($items[$a][0]), $conf['radioWrap.']) . '</label>';
							} else {
								$option .= $this->stdWrap(trim($items[$a][0]), $conf['radioWrap.']);
							}
 						}

						if ($conf['accessibility'])	{
							$accessibilityWrap = $conf['radioWrap.']['accessibilityWrap'];

							$search = array(
								'###RADIO_FIELD_ID###',
								'###RADIO_GROUP_LABEL###'
							);
							$replace = array(
								$elementIdAttribute,
								$confData['label']
							);
							$accessibilityWrap = str_replace($search, $replace, $accessibilityWrap);

							$option = $this->wrap($option, $accessibilityWrap);
						}

						$fieldCode = $option;
					break;
					case 'hidden':
						$value = trim($parts[2]);
						if (strlen($value) && t3lib_div::inList('recipient_copy,recipient',$confData['fieldname']) && $GLOBALS['TYPO3_CONF_VARS']['FE']['secureFormmail'])	{
							break;
						}
						if (strlen($value) && t3lib_div::inList('recipient_copy,recipient',$confData['fieldname']))	{
							$value = $GLOBALS['TSFE']->codeString($value);
						}
						$hiddenfields.= sprintf('<input type="hidden" name="%s"%s value="%s" />',
							$confData['fieldname'], $elementIdAttribute, htmlspecialchars($value));
					break;
					case 'property':
						if (t3lib_div::inList('type,locationData,goodMess,badMess,emailMess',$confData['fieldname']))	{
							$value=trim($parts[2]);
							$propertyOverride[$confData['fieldname']] = $value;
							$conf[$confData['fieldname']] = $value;
						}
					break;
					case 'submit':
						$value=trim($parts[2]);
						if ($conf['image.'])	{
							$this->data[$this->currentValKey] = $value;
							$image = $this->IMG_RESOURCE($conf['image.']);
							$params = $conf['image.']['params'] ? ' '.$conf['image.']['params'] : '';
							$params.= $this->getAltParam($conf['image.'], false);
							$params.= $addParams;
						} else {
							$image = '';
						}
						if ($image)	{
							$fieldCode=sprintf('<input type="image" name="%s"%s src="%s"%s />',
								$confData['fieldname'], $elementIdAttribute, $image, $params);
						} else	{
							$fieldCode=sprintf('<input type="submit" name="%s"%s value="%s"%s />',
								$confData['fieldname'], $elementIdAttribute, t3lib_div::deHSCentities(htmlspecialchars($value)), $addParams);
						}
					break;
					case 'reset':
						$value=trim($parts[2]);
						$fieldCode=sprintf('<input type="reset" name="%s"%s value="%s"%s />',
							$confData['fieldname'], $elementIdAttribute, t3lib_div::deHSCentities(htmlspecialchars($value)), $addParams);
					break;
					case 'label':
						$fieldCode = nl2br(htmlspecialchars(trim($parts[2])));
					break;
					default:
						$confData['type'] = 'comment';
						$fieldCode = trim($parts[2]).'&nbsp;';
					break;
				}
				if ($fieldCode)	{

						// Checking for special evaluation modes:
					if (t3lib_div::inList('textarea,input,password',$confData['type']) && strlen(trim($parts[3])))	{
						$modeParameters = t3lib_div::trimExplode(':',$parts[3]);
					} else {
						$modeParameters = array();
					}

						// Adding evaluation based on settings:
					switch ((string)$modeParameters[0])	{
						case 'EREG':
							$fieldlist[] = '_EREG';
							$fieldlist[] = $modeParameters[1];
							$fieldlist[] = $modeParameters[2];
							$fieldlist[] = $confData['fieldname'];
							$fieldlist[] = $confData['label'];
							$confData['required'] = 1;	// Setting this so "required" layout is used.
						break;
						case 'EMAIL':
							$fieldlist[] = '_EMAIL';
							$fieldlist[] = $confData['fieldname'];
							$fieldlist[] = $confData['label'];
							$confData['required'] = 1;	// Setting this so "required" layout is used.
						break;
						default:
							if ($confData['required'])	{
								$fieldlist[] = $confData['fieldname'];
								$fieldlist[] = $confData['label'];
							}
						break;
					}

						// Field:
					$fieldLabel = $confData['label'];
					if ($conf['accessibility'] && trim($fieldLabel) && !preg_match('/^(label|hidden|comment)$/',$confData['type']))	{
						$fieldLabel = '<label for="'.$prefix.$fName.'">'.$fieldLabel.'</label>';
					}

						// Getting template code:
					$fieldCode = $this->stdWrap($fieldCode, $conf['fieldWrap.']);
					$labelCode = $this->stdWrap($fieldLabel, $conf['labelWrap.']);
					$commentCode = $this->stdWrap($confData['label'], $conf['commentWrap.']); // RTF
					$result = $conf['layout'];
					if ($conf['REQ'] && $confData['required'])	{
						if (is_array($conf['REQ.']['fieldWrap.']))
							$fieldCode = $this->stdWrap($fieldCode, $conf['REQ.']['fieldWrap.']);
						if (is_array($conf['REQ.']['labelWrap.']))
							$labelCode = $this->stdWrap($fieldLabel, $conf['REQ.']['labelWrap.']);
						if ($conf['REQ.']['layout'])	{
							$result = $conf['REQ.']['layout'];
						}
					}
					if ($confData['type']=='comment' && $conf['COMMENT.']['layout'])	{
						$result = $conf['COMMENT.']['layout'];
					}
					if ($confData['type']=='check' && $conf['CHECK.']['layout'])	{
						$result = $conf['CHECK.']['layout'];
					}
					if ($confData['type']=='radio' && $conf['RADIO.']['layout'])	{
						$result = $conf['RADIO.']['layout'];
					}
					if ($confData['type']=='label' && $conf['LABEL.']['layout']) {
						$result = $conf['LABEL.']['layout'];
					}
					$result = str_replace('###FIELD###',$fieldCode,$result);
					$result = str_replace('###LABEL###',$labelCode,$result);
					$result = str_replace('###COMMENT###',$commentCode,$result); //RTF
					$content.= $result;
				}
			}
		}
		if ($conf['stdWrap.'])	{ $content = $this->stdWrap($content, $conf['stdWrap.']); }


			// redirect (external: where to go afterwards. internal: where to submit to)
		$theRedirect = $this->stdWrap($conf['redirect'], $conf['redirect.']);			// redirect should be set to the page to redirect to after an external script has been used. If internal scripts is used, and if no 'type' is set that dictates otherwise, redirect is used as the url to jump to as long as it's an integer (page)
		$page = $GLOBALS['TSFE']->page;
		if (!$theRedirect)	{		// Internal: Just submit to current page
			$LD = $GLOBALS['TSFE']->tmpl->linkData($page, $conf['target'], $conf['no_cache'],'index.php', '', $this->getClosestMPvalueForPage($page['uid']));
		} elseif (t3lib_div::testInt($theRedirect))	{		// Internal: Submit to page with ID $theRedirect
			$page = $GLOBALS['TSFE']->sys_page->getPage_noCheck($theRedirect);
			$LD = $GLOBALS['TSFE']->tmpl->linkData($page, $conf['target'], $conf['no_cache'],'index.php', '', $this->getClosestMPvalueForPage($page['uid']));
		} else {	// External URL, redirect-hidden field is rendered!
			$LD = $GLOBALS['TSFE']->tmpl->linkData($page, $conf['target'], $conf['no_cache'],'', '', $this->getClosestMPvalueForPage($page['uid']));
			$LD['totalURL'] = $theRedirect;
			$hiddenfields.= '<input type="hidden" name="redirect" value="'.htmlspecialchars($LD['totalURL']).'" />';		// 18-09-00 added
		}

			// Formtype (where to submit to!):
		$formtype = $propertyOverride['type'] ? $propertyOverride['type'] : $this->stdWrap($conf['type'], $conf['type.']);
		if (t3lib_div::testInt($formtype))	{	// Submit to a specific page
			$page = $GLOBALS['TSFE']->sys_page->getPage_noCheck($formtype);
			$LD_A = $GLOBALS['TSFE']->tmpl->linkData($page, $conf['target'], $conf['no_cache'], '', '', $this->getClosestMPvalueForPage($page['uid']));
			$action = $LD_A['totalURL'];
		} elseif ($formtype)	{	// Submit to external script
			$LD_A = $LD;
			$action = $formtype;
		} elseif (t3lib_div::testInt($theRedirect))	{
			$LD_A = $LD;
			$action = $LD_A['totalURL'];
		} else {		// Submit to "nothing" - which is current page
			$LD_A = $GLOBALS['TSFE']->tmpl->linkData($GLOBALS['TSFE']->page, $conf['target'], $conf['no_cache'], '', '', $this->getClosestMPvalueForPage($page['uid']));
			$action = $LD_A['totalURL'];
		}

			// Recipient:
		$theEmail = $this->stdWrap($conf['recipient'], $conf['recipient.']);
		if ($theEmail && !$GLOBALS['TYPO3_CONF_VARS']['FE']['secureFormmail'])	{
			$theEmail = $GLOBALS['TSFE']->codeString($theEmail);
			$hiddenfields.= '<input type="hidden" name="recipient" value="'.htmlspecialchars($theEmail).'" />';
		}

			// location data:
		if ($conf['locationData'])	{
			if ($conf['locationData']=='HTTP_POST_VARS' && isset($_POST['locationData']))	{
				$locationData = t3lib_div::_POST('locationData');
			} else {
				$locationData = $GLOBALS['TSFE']->id.':'.$this->currentRecord;	// locationData is [hte page id]:[tablename]:[uid of record]. Indicates on which page the record (from tablename with uid) is shown. Used to check access.
			}
			$hiddenfields.='<input type="hidden" name="locationData" value="'.htmlspecialchars($locationData).'" />';
		}

			// hidden fields:
		if (is_array($conf['hiddenFields.']))	{
			foreach ($conf['hiddenFields.'] as $hF_key => $hF_conf) {
				if (substr($hF_key,-1)!='.')	{
					$hF_value = $this->cObjGetSingle($hF_conf,$conf['hiddenFields.'][$hF_key.'.'],'hiddenfields');
					if (strlen($hF_value) && t3lib_div::inList('recipient_copy,recipient',$hF_key))	{
						if ($GLOBALS['TYPO3_CONF_VARS']['FE']['secureFormmail'])	{
							continue;
						}
						$hF_value = $GLOBALS['TSFE']->codeString($hF_value);
					}
					$hiddenfields.= '<input type="hidden" name="'.$hF_key.'" value="'.htmlspecialchars($hF_value).'" />';
				}
			}
		}

			// Wrap all hidden fields in a div tag (see http://bugs.typo3.org/view.php?id=678)
		$hiddenfields = '<div style="display:none;">'.$hiddenfields.'</div>';

		if ($conf['REQ'])	{
			$validateForm = ' onsubmit="return validateForm(\'' .
				$formname . '\',\'' . implode(',',$fieldlist) . '\',' .
				t3lib_div::quoteJSvalue($conf['goodMess']) . ',' .
				t3lib_div::quoteJSvalue($conf['badMess']) . ',' .
				t3lib_div::quoteJSvalue($conf['emailMess']) . ')"';
			$GLOBALS['TSFE']->additionalHeaderData['JSFormValidate'] =
				'<script type="text/javascript" src="' .
				t3lib_div::createVersionNumberedFilename($GLOBALS['TSFE']->absRefPrefix .
					't3lib/jsfunc.validateform.js') .
				'"></script>';
		} else {
			$validateForm = '';
		}

			// Create form tag:
		$theTarget = ($theRedirect?$LD['target']:$LD_A['target']);
		$content = array(
			'<form'.
				' action="'.htmlspecialchars($action).'"'.
				' id="'.$formname.'"'.($xhtmlStrict ? '' : ' name="'.$formname.'"').
				' enctype="'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'].'"'.
				' method="'.($conf['method']?$conf['method']:'post').'"'.
				($theTarget ? ' target="'.$theTarget.'"' : '').
				$validateForm.
				'>',
			$hiddenfields.$content,
			'</form>'
		);

		if ($conf['arrayReturnMode'])	{
			$content['validateForm']=$validateForm;
			$content['formname']=$formname;
			return $content;
		} else {
			return implode('',$content);
		}
	}

	/**
	 * Rendering the cObject, SEARCHRESULT
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=368&cHash=d00731cd7b
	 */
	function SEARCHRESULT($conf)	{
		if (t3lib_div::_GP('sword') && t3lib_div::_GP('scols'))	{
			$search = t3lib_div::makeInstance('tslib_search');
			$search->register_and_explode_search_string(t3lib_div::_GP('sword'));
			$search->register_tables_and_columns(t3lib_div::_GP('scols'),$conf['allowedCols']);
				// depth
			$depth=100;
				// the startId is found
			$theStartId=0;
			if (t3lib_div::testInt(t3lib_div::_GP('stype')))	{
				$temp_theStartId=t3lib_div::_GP('stype');
				$rootLine = $GLOBALS['TSFE']->sys_page->getRootLine($temp_theStartId);
					// The page MUST have a rootline with the Level0-page of the current site inside!!
				foreach ($rootLine as $val) {
					if($val['uid']==$GLOBALS['TSFE']->tmpl->rootLine[0]['uid'])	{
						$theStartId = $temp_theStartId;
					}
				}
			} else if (t3lib_div::_GP('stype'))	{
				if (substr(t3lib_div::_GP('stype'),0,1)=='L')	{
					$pointer = intval(substr(t3lib_div::_GP('stype'),1));
					$theRootLine = $GLOBALS['TSFE']->tmpl->rootLine;
						// location Data:
					$locDat_arr = explode(':',t3lib_div::_POST('locationData'));
					$pId = intval($locDat_arr[0]);
					if ($pId)	{
						$altRootLine = $GLOBALS['TSFE']->sys_page->getRootLine($pId);
						ksort($altRootLine);
						if (count($altRootLine))	{
								// check if the rootline has the real Level0 in it!!
							$hitRoot=0;
							$theNewRoot=array();
							foreach ($altRootLine as $val) {
								if($hitRoot || $val['uid']==$GLOBALS['TSFE']->tmpl->rootLine[0]['uid'])	{
									$hitRoot=1;
									$theNewRoot[]=$val;
								}
							}
							if ($hitRoot)	{
								$theRootLine = $theNewRoot;		// Override the real rootline if any thing
							}
						}
					}
					$key = $this->getKey($pointer,$theRootLine);
					$theStartId = $theRootLine[$key]['uid'];
				}
			}
			if (!$theStartId)	{
					// If not set, we use current page
				$theStartId = $GLOBALS['TSFE']->id;
			}
				// generate page-tree
			$search->pageIdList.= $this->getTreeList(-1*$theStartId,$depth);

			$endClause = 'pages.uid IN ('.$search->pageIdList.')
				AND pages.doktype in ('.$GLOBALS['TYPO3_CONF_VARS']['FE']['content_doktypes'].($conf['addExtUrlsAndShortCuts']?',3,4':'').')
				AND pages.no_search=0'.
				$this->enableFields($search->fTable).
				$this->enableFields('pages');

			if ($conf['languageField.'][$search->fTable])	{
				$endClause.= ' AND '.$search->fTable.'.'.$conf['languageField.'][$search->fTable].' = '.intval($GLOBALS['TSFE']->sys_language_uid);	// (using sys_language_uid which is the ACTUAL language of the page. sys_language_content is only for selecting DISPLAY content!)
			}

				// build query
			$search->build_search_query($endClause);

				// count...
			if (t3lib_div::testInt(t3lib_div::_GP('scount')))	{
				$search->res_count = t3lib_div::_GP('scount');
			} else {
				$search->count_query();
			}

				// range
			$spointer = intval(t3lib_div::_GP('spointer'));
			if (isset($conf['range']))	{
				$theRange = intval($conf['range']);
			} else {
				$theRange = 20;
			}

				// Order By:
			if (!$conf['noOrderBy'])	{
				$search->queryParts['ORDERBY'] = 'pages.lastUpdated, pages.tstamp';
			}

			$search->queryParts['LIMIT'] = $spointer.','.$theRange;

				// search...
			$search->execute_query();
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($search->result))	{
				$GLOBALS['TSFE']->register['SWORD_PARAMS'] = $search->get_searchwords();

				$total = $search->res_count;
				$rangeLow = t3lib_div::intInRange($spointer+1,1,$total);
				$rangeHigh = t3lib_div::intInRange($spointer+$theRange,1,$total);
					// prev/next url:
				$LD = $GLOBALS['TSFE']->tmpl->linkData($GLOBALS['TSFE']->page,$conf['target'],1,'', '', $this->getClosestMPvalueForPage($GLOBALS['TSFE']->page['uid']));
				$targetPart = $LD['target'] ? ' target="'.htmlspecialchars($LD['target']).'"' : '';
				$urlParams = $this->URLqMark($LD['totalURL'],
						'&sword='.rawurlencode(t3lib_div::_GP('sword')).
						'&scols='.rawurlencode(t3lib_div::_GP('scols')).
						'&stype='.rawurlencode(t3lib_div::_GP('stype')).
						'&scount='.$total);
					// substitution:
				$result= $this->cObjGetSingle($conf['layout'],$conf['layout.'], 'layout');
				$result = str_replace('###RANGELOW###',$rangeLow,$result);
				$result = str_replace('###RANGEHIGH###',$rangeHigh,$result);
				$result = str_replace('###TOTAL###',$total,$result);

				if ($rangeHigh<$total)	{
					$next = $this->cObjGetSingle($conf['next'], $conf['next.'], 'next');
					$next = '<a href="'.htmlspecialchars($urlParams.'&spointer='.($spointer+$theRange)).'"'.$targetPart.$GLOBALS['TSFE']->ATagParams.'>'.$next.'</a>';
				} else $next='';
				$result = str_replace('###NEXT###',$next,$result);

				if ($rangeLow>1)	{
					$prev = $this->cObjGetSingle($conf['prev'], $conf['prev.'], 'prev');
					$prev = '<a href="'.htmlspecialchars($urlParams.'&spointer='.($spointer-$theRange)).'"'.$targetPart.$GLOBALS['TSFE']->ATagParams.'>'.$prev.'</a>';
				} else $prev='';
				$result = str_replace('###PREV###',$prev,$result);

					// searching result
				$theValue = $this->cObjGetSingle($conf['resultObj'], $conf['resultObj.'],'resultObj');
				$cObj = t3lib_div::makeInstance('tslib_cObj');
				$cObj->setParent($this->data,$this->currentRecord);
				$renderCode='';
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($search->result))	{
						// versionOL() here? This is search result displays, is that possible to preview anyway? Or are records selected here already future versions?
					$cObj->start($row);
					$renderCode.=$cObj->cObjGetSingle($conf['renderObj'], $conf['renderObj.'],'renderObj');
				}
				$theValue.=$this->wrap($renderCode,$conf['renderWrap']);
				$theValue = str_replace('###RESULT###',$theValue,$result);
			} else {
				$theValue = $this->cObjGetSingle($conf['noResultObj'], $conf['noResultObj.'],'noResultObj');
			}

			$GLOBALS['TT']->setTSlogMessage('Search in fields:   '.$search->listOfSearchFields);

				// wrapping
			$content=$theValue;
			if ($conf['wrap']) {
				$content=$this->wrap($content, $conf['wrap']);
			}
			if ($conf['stdWrap.']) {
				$content=$this->stdWrap($content, $conf['stdWrap.']);
			}
				// returning
			$GLOBALS['TSFE']->set_no_cache();
			return $content;
		}
	}

	/**
	 * Rendering the cObject, PHP_SCRIPT, PHP_SCRIPT_INT and PHP_SCRIPT_EXT
	 *
	 * @param	array		Array of TypoScript properties
	 * @param	string		If "INT", then rendering "PHP_SCRIPT_INT"; If "EXT", then rendering "PHP_SCRIPT_EXT"; Default is rendering "PHP_SCRIPT" (cached)
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=370&cHash=aa107f2ad8
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=371&cHash=53f71d025e
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=372&cHash=91fe391e1c
	 */
	function PHP_SCRIPT($conf,$ext='')	{
		$incFile = $GLOBALS['TSFE']->tmpl->getFileName($conf['file']);
		$content='';
		if ($incFile && $GLOBALS['TSFE']->checkFileInclude($incFile))	{
			switch($ext)	{
				case 'INT':
				case 'EXT':
					$substKey = $ext.'_SCRIPT.'.$GLOBALS['TSFE']->uniqueHash();
					$content.='<!--'.$substKey.'-->';
					$GLOBALS['TSFE']->config[$ext.'incScript'][$substKey] = array(
						'file'=>$incFile,
						'conf'=>$conf,
						'type'=>'SCRIPT'
					);
					if ($ext=='INT')	{
						$GLOBALS['TSFE']->config[$ext.'incScript'][$substKey]['cObj'] = serialize($this);
					} else {
						$GLOBALS['TSFE']->config[$ext.'incScript'][$substKey]['data'] = $this->data;
					}
				break;
				default:
						// Added 31-12-00: Make backup...
					$this->oldData = $this->data;
						// Include file..
					include('./'.$incFile);
						// Added 31-12-00: restore...
					if ($RESTORE_OLD_DATA)	{
						$this->data = $this->oldData;
					}
				break;
			}
		}
		return $content;
	}

	/**
	 * Rendering the cObject, TEMPLATE
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=373&cHash=109a171b1e
	 * @see substituteMarkerArrayCached()
	 */
	function TEMPLATE($conf)	{
		$subparts = Array();
		$marks = Array();
		$wraps = Array();
		$content='';

		list($PRE,$POST) = explode('|',$conf['markerWrap'] ? $conf['markerWrap'] : '### | ###');
		$POST = trim($POST);
		$PRE  = trim($PRE);

			// Getting the content
		$content = $this->cObjGetSingle($conf['template'],$conf['template.'],'template');
		if ($conf['workOnSubpart'])	{
			$content = $this->getSubpart($content, $PRE.$conf['workOnSubpart'].$POST);
		}

			// Fixing all relative paths found:
		if ($conf['relPathPrefix'])	{
			$htmlParser = t3lib_div::makeInstance('t3lib_parsehtml');
			$content = $htmlParser->prefixResourcePath($conf['relPathPrefix'],$content,$conf['relPathPrefix.']);
		}

		if ($content)	{
			if ($conf['nonCachedSubst'])	{		// NON-CACHED:
					// Getting marks
				if (is_array($conf['marks.']))	{
					foreach ($conf['marks.'] as $theKey => $theValue) {
						if (!strstr($theKey,'.'))	{
							$content = str_replace(
								$PRE.$theKey.$POST,
								$this->cObjGetSingle($theValue,$conf['marks.'][$theKey.'.'],'marks.'.$theKey),
								$content);
						}
					}
				}

					// Getting subparts.
				if (is_array($conf['subparts.']))	{
					foreach ($conf['subparts.'] as $theKey => $theValue) {
						if (!strstr($theKey,'.'))	{
							$subpart = $this->getSubpart($content, $PRE.$theKey.$POST);
							if ($subpart)	{
								$this->setCurrentVal($subpart);
								$content = $this->substituteSubpart(
									$content,
									$PRE.$theKey.$POST,
									$this->cObjGetSingle($theValue,$conf['subparts.'][$theKey.'.'],'subparts.'.$theKey),
									1
								);
							}
						}
					}
				}
					// Getting subpart wraps
				if (is_array($conf['wraps.']))	{
					foreach ($conf['wraps.'] as $theKey => $theValue) {
						if (!strstr($theKey,'.'))	{
							$subpart = $this->getSubpart($content, $PRE.$theKey.$POST);
							if ($subpart)	{
								$this->setCurrentVal($subpart);
								$content = $this->substituteSubpart(
									$content,
									$PRE.$theKey.$POST,
									explode('|',$this->cObjGetSingle($theValue,$conf['wraps.'][$theKey.'.'],'wraps.'.$theKey)),
									1
								);
							}
						}
					}
				}
			} else {	// CACHED
					// Getting subparts.
				if (is_array($conf['subparts.']))	{
					foreach ($conf['subparts.'] as $theKey => $theValue) {
						if (!strstr($theKey,'.'))	{
							$subpart = $this->getSubpart($content, $PRE.$theKey.$POST);
							if ($subpart)	{
								$GLOBALS['TSFE']->register['SUBPART_'.$theKey] = $subpart;
								$subparts[$theKey]['name'] = $theValue;
								$subparts[$theKey]['conf'] = $conf['subparts.'][$theKey.'.'];
							}
						}
					}
				}
					// Getting marks
				if (is_array($conf['marks.']))	{
					foreach ($conf['marks.'] as $theKey => $theValue) {
						if (!strstr($theKey,'.'))	{
							$marks[$theKey]['name'] = $theValue;
							$marks[$theKey]['conf'] = $conf['marks.'][$theKey.'.'];
						}
					}
				}
					// Getting subpart wraps
				if (is_array($conf['wraps.']))	{
					foreach ($conf['wraps.'] as $theKey => $theValue) {
						if (!strstr($theKey,'.'))	{
							$wraps[$theKey]['name'] = $theValue;
							$wraps[$theKey]['conf'] = $conf['wraps.'][$theKey.'.'];
						}
					}
				}
					// Getting subparts
				$subpartArray =array();
				foreach ($subparts as $theKey => $theValue) {
						// Set current with the content of the subpart...
					$this->data[$this->currentValKey] = $GLOBALS['TSFE']->register['SUBPART_'.$theKey];
						// Get subpart cObject and substitute it!
					$subpartArray[$PRE.$theKey.$POST] = $this->cObjGetSingle($theValue['name'],$theValue['conf'],'subparts.'.$theKey);
				}
				$this->data[$this->currentValKey] = '';	// Reset current to empty

					// Getting marks
				$markerArray =array();
				foreach ($marks as $theKey => $theValue) {
					$markerArray[$PRE.$theKey.$POST] = $this->cObjGetSingle($theValue['name'],$theValue['conf'],'marks.'.$theKey);
				}
					// Getting wraps
				$subpartWraps =array();
				foreach ($wraps as $theKey => $theValue) {
					$subpartWraps[$PRE.$theKey.$POST] = explode('|',$this->cObjGetSingle($theValue['name'],$theValue['conf'],'wraps.'.$theKey));
				}

					// Substitution
				if ($conf['substMarksSeparately'])	{
					$content = $this->substituteMarkerArrayCached($content,array(),$subpartArray,$subpartWraps);
					$content = $this->substituteMarkerArray($content, $markerArray);
				} else {
					$content = $this->substituteMarkerArrayCached($content,$markerArray,$subpartArray,$subpartWraps);
				}
			}
		}
		return $content;
	}

	/**
	 * Rendering the cObject, MULTIMEDIA
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=374&cHash=efd88ab4a9
	 */
	function MULTIMEDIA($conf)	{
		$content='';
		$filename=$this->stdWrap($conf['file'],$conf['file.']);
		$incFile = $GLOBALS['TSFE']->tmpl->getFileName($filename);
		if ($incFile)	{
			$fileinfo = t3lib_div::split_fileref($incFile);
			if (t3lib_div::inList('txt,html,htm',$fileinfo['fileext']))	{
				$content = $GLOBALS['TSFE']->tmpl->fileContent($incFile);
			} else {
					// default params...
				$parArray=array();
					// src is added
				$parArray['src']='src="'.$GLOBALS['TSFE']->absRefPrefix.$incFile.'"';
				if (t3lib_div::inList('au,wav,mp3',$fileinfo['fileext']))	{
				}
				if (t3lib_div::inList('avi,mov,mpg,asf,wmv',$fileinfo['fileext']))	{
					$parArray['width'] = 'width="' . ($conf['width'] ? $conf['width'] : 200) . '"';
					$parArray['height'] = 'height="' . ($conf['height'] ? $conf['height'] : 200) . '"';
				}
				if (t3lib_div::inList('swf,swa,dcr',$fileinfo['fileext']))	{
					$parArray['quality'] = 'quality="high"';
				}
				if (t3lib_div::inList('class',$fileinfo['fileext']))	{
					$parArray['width'] = 'width="' . ($conf['width'] ? $conf['width'] : 200) . '"';
					$parArray['height'] = 'height="' . ($conf['height'] ? $conf['height'] : 200) . '"';
				}

					// fetching params
				$lines = explode(LF, $this->stdWrap($conf['params'],$conf['params.']));
				foreach ($lines as $l) {
					$parts = explode('=', $l);
					$parameter = strtolower(trim($parts[0]));
					$value = trim($parts[1]);
					if ((string)$value!='')	{
						$parArray[$parameter] = $parameter.'="'.htmlspecialchars($value).'"';
					} else {
						unset($parArray[$parameter]);
					}
				}
				if ($fileinfo['fileext']=='class')	{
					unset($parArray['src']);
					$parArray['code'] = 'code="'.htmlspecialchars($fileinfo['file']).'"';
					$parArray['codebase'] = 'codebase="'.htmlspecialchars($fileinfo['path']).'"';
					$content='<applet '.implode(' ',$parArray).'></applet>';
				} else {
					$content='<embed '.implode(' ',$parArray).'></embed>';
				}
			}
		}

		if ($conf['stdWrap.']) {
			$content=$this->stdWrap($content, $conf['stdWrap.']);
		}

		return $content;
	}

	/**
	 * Rendering the cObject, SWFOBJECT
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 */
	public function MEDIA($conf) {
		$content = '';
		$flexParams = $this->stdWrap($conf['flexParams'], $conf['flexParams.']);
		if (substr($flexParams, 0, 1) === '<') {
			// it is a content element
			$this->readFlexformIntoConf($flexParams, $conf['parameter.']);
			$url = $conf['parameter.']['mmFile'];
			$url = $this->stdWrap($url, $conf['file.']);
		} else {
			// it is a TS object
			$url = $this->stdWrap($conf['file'], $conf['file.']);
		}

		$mode = is_file(PATH_site . $url) ? 'file' : 'url';
		if ($mode === 'file') {
			$filename = $GLOBALS['TSFE']->tmpl->getFileName($url);
			$fileinfo = t3lib_div::split_fileref($filename);
			$conf['file'] = $filename;
		} else {
			$conf['file'] = $this->typoLink_URL(array('parameter' => $url));
		}

		$renderType = $conf['renderType'];
		if (isset($conf['parameter.']['mmRenderType'])) {
			$renderType = $conf['parameter.']['mmRenderType'];
		}
		if ($renderType === 'auto') {
				// default renderType is swf
			$renderType = 'swf';
			$handler = array_keys($conf['fileExtHandler.']);
			if (in_array($fileinfo['fileext'], $handler)) {
				$renderType = strtolower($conf['fileExtHandler.'][$fileinfo['fileext']]);
			}
		}

		$forcePlayer = isset($conf['parameter.']['mmFile']) ? intval($conf['parameter.']['mmforcePlayer']) :  $conf['forcePlayer'];
		$conf['forcePlayer'] = $forcePlayer;

		$conf['type'] = isset($conf['parameter.']['mmType']) ? $conf['parameter.']['mmType'] : $conf['type'];
		$mime = $renderType . 'object';
		$typeConf = $conf['mimeConf.'][$mime . '.'][$conf['type'] . '.'] ? $conf['mimeConf.'][$mime . '.'][$conf['type'] . '.'] : array();
		$conf['predefined'] = array();

		$width = intval($conf['parameter.']['mmWidth']);
		$height = intval($conf['parameter.']['mmHeight']);
		if ($width) {
			$conf['width'] = $width;
		} else {
			$conf['width'] = intval($conf['width']) ? $conf['width'] : $typeConf['defaultWidth'];
		}
		if ($height) {
			$conf['height'] = $height;
		} else {
			$conf['height'] = intval($conf['height']) ? $conf['height'] : $typeConf['defaultHeight'];
		}

		if (is_array($conf['parameter.']['mmMediaOptions'])) {
			$params = array();
			foreach ($conf['parameter.']['mmMediaOptions'] as $key => $value) {
				if ($key == 'mmMediaCustomParameterContainer') {
					foreach ($value as $val) {
						//custom parameter entry
						$rawTS = $val['mmParamCustomEntry'];
						//read and merge
						$tmp = t3lib_div::trimExplode(LF, $rawTS);
						if (count($tmp)) {
							foreach ($tmp as $tsLine) {
								if (substr($tsLine, 0, 1) != '#' && $pos = strpos($tsLine, '.')) {
									$parts[0] = substr($tsLine, 0, $pos);
									$parts[1] = substr($tsLine, $pos + 1);
									$valueParts = t3lib_div::trimExplode('=', $parts[1], true);

									switch (strtolower($parts[0])) {
										case 'flashvars':
											$conf['flashvars.'][$valueParts[0]] = $valueParts[1];
										break;
										case 'params':
											$conf['params.'][$valueParts[0]] = $valueParts[1];
										break;
										case 'attributes':
											$conf['attributes.'][$valueParts[0]] = $valueParts[1];
										break;
									}
								}
							}
						}
					}
				} elseif ($key == 'mmMediaOptionsContainer') {
					foreach ($value as $val) {
						if (isset($val['mmParamSet'])) {
							$pName = $val['mmParamName'];
							$pSet = $val['mmParamSet'];
							$pValue = $pSet == 2 ? $val['mmParamValue'] : ($pSet == 0 ? 'false' : 'true');
							$conf['predefined'][$pName] = $pValue;
						}
					}
				}
			}
		}

			// render MEDIA
		if ($mode == 'url' && !$forcePlayer) {
				// url is called direct, not with player
			if ($url == '' && !$conf['allowEmptyUrl']) {
				return '<p style="background-color: yellow;">' . $GLOBALS['TSFE']->sL('LLL:EXT:cms/locallang_ttc.xml:media.noFile', true) . '</p>';
			}
			$conf = array_merge($conf['mimeConf.']['swfobject.'], $conf);
			$conf[$conf['type'] . '.']['player'] = strpos($url, '://') === false ? 'http://' . $url : $url;
			$conf['installUrl'] = 'null';
			$conf['flashvars'] = array_merge((array) $conf['flashvars'], $conf['predefined']);
		}

		switch ($renderType) {
			case 'swf':
				$conf[$conf['type'] . '.'] = array_merge($conf['mimeConf.']['swfobject.'][$conf['type'] . '.'], $typeConf);
				$conf = array_merge($conf['mimeConf.']['swfobject.'], $conf);
				unset($conf['mimeConf.']);
				$conf['flashvars.'] = array_merge((array) $conf['flashvars.'], $conf['predefined']);
				$content = $this->SWFOBJECT($conf);
			break;
			case 'qt':
				$conf[$conf['type'] . '.'] = array_merge($conf['mimeConf.']['swfobject.'][$conf['type'] . '.'], $typeConf);
				$conf = array_merge($conf['mimeConf.']['qtobject.'], $conf);
				unset($conf['mimeConf.']);
				$conf['params.'] = array_merge((array) $conf['params.'], $conf['predefined']);
				$content = $this->QTOBJECT($conf);
			break;
			case 'embed':
				$paramsArray = array_merge((array) $typeConf['default.']['params.'], (array) $conf['params.'], $conf['predefined']);
				$conf['params']= '';
				foreach ($paramsArray as $key => $value) {
					$conf['params'] .= $key . '=' . $value . LF;
				}
				$content = $this->MULTIMEDIA($conf);
			break;
			default:
				if (is_array ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['customMediaRender'])) {
					foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['customMediaRender'] as $classRef) {
						$hookObj = t3lib_div::getUserObj($classRef);
						$conf['file'] = $url;
						$conf['mode'] = $mode;
						$content = $hookObj->customMediaRender($renderType, $conf, $this);
					}
				}
		}

		return $content;
	}


	/**
	 * Rendering the cObject, SWFOBJECT
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 */
	public function SWFOBJECT($conf) {
		$content = '';
		$flashvars = $params = $attributes = '';
		$prefix = '';
		if ($GLOBALS['TSFE']->baseUrl) {
			$prefix = $GLOBALS['TSFE']->baseUrl;
		}
		if ($GLOBALS['TSFE']->absRefPrefix) {
			$prefix = $GLOBALS['TSFE']->absRefPrefix;
		};

		$typeConf = $conf[$conf['type'] . '.'];

			//add SWFobject js-file
		$GLOBALS['TSFE']->getPageRenderer()->addJsFile(TYPO3_mainDir . 'contrib/flashmedia/swfobject/swfobject.js');

		$player = $this->stdWrap($conf[$conf['type'] . '.']['player'], $conf[$conf['type'] . '.']['player.']);
		$installUrl = $conf['installUrl'] ? $conf['installUrl'] : $prefix . TYPO3_mainDir . 'contrib/flashmedia/swfobject/expressInstall.swf';
		$filename = $this->stdWrap($conf['file'], $conf['file.']);
		if ($filename && $conf['forcePlayer']) {
			if (strpos($filename, '://') !== FALSE) {
				$conf['flashvars.']['file'] = $filename;
			} else {
				if ($prefix) {
					$conf['flashvars.']['file'] = $prefix . $filename;
				} else {
					$conf['flashvars.']['file'] = str_repeat('../', substr_count($player, '/')) . $filename;
				}

			}
		} else {
			$player = $filename;
		}
			// Write calculated values in conf for the hook
		$conf['player'] = $player;
		$conf['installUrl'] = $installUrl;
		$conf['filename'] = $filename;
		$conf['prefix'] = $prefix;

			// merge with default parameters
		$conf['flashvars.'] = array_merge((array) $typeConf['default.']['flashvars.'], (array) $conf['flashvars.']);
		$conf['params.'] = array_merge((array) $typeConf['default.']['params.'], (array) $conf['params.']);
		$conf['attributes.'] = array_merge((array) $typeConf['default.']['attributes.'], (array) $conf['attributes.']);
		$conf['embedParams'] = 'flashvars, params, attributes';

			// Hook for manipulating the conf array, it's needed for some players like flowplayer
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['swfParamTransform'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['swfParamTransform'] as $classRef) {
				t3lib_div::callUserFunction($classRef, $conf, $this);
			}
		}
		if (is_array($conf['flashvars.'])) {
			t3lib_div::remapArrayKeys($conf['flashvars.'], $typeConf['mapping.']['flashvars.']);
		}
		$flashvars = 'var flashvars = ' . (count($conf['flashvars.']) ? json_encode($conf['flashvars.']) : '{}') . ';';

		if (is_array($conf['params.'])) {
			t3lib_div::remapArrayKeys($conf['params.'], $typeConf['mapping.']['params.']);
		}
		$params = 'var params = ' . (count($conf['params.']) ? json_encode($conf['params.']) : '{}') . ';';

		if (is_array($conf['attributes.'])) {
			t3lib_div::remapArrayKeys($conf['attributes.'], $typeConf['attributes.']['params.']);
		}
		$attributes = 'var attributes = ' . (count($conf['attributes.']) ? json_encode($conf['attributes.']) : '{}') . ';';

		$flashVersion = $this->stdWrap($conf['flashVersion'], $conf['flashVersion.']);
		if (!$flashVersion) {
		 	$flashVersion = '9';
		}

		$replaceElementIdString = uniqid('mmswf');
		$GLOBALS['TSFE']->register['MMSWFID'] = $replaceElementIdString;

		$alternativeContent = $this->stdWrap($conf['alternativeContent'], $conf['alternativeContent.']);

		$layout = $this->stdWrap($conf['layout'], $conf['layout.']);
		$layout = str_replace('###ID###', $replaceElementIdString, $layout);
		$layout = str_replace('###SWFOBJECT###', '<div id="' . $replaceElementIdString . '">' . $alternativeContent . '</div>', $layout);

		$width = $this->stdWrap($conf['width'], $conf['width.']);
		$height = $this->stdWrap($conf['height'], $conf['height.']);

		$width = $width ? $width : $conf[$conf['type'] . '.']['defaultWidth'];
		$height = $height ? $height : $conf[$conf['type'] . '.']['defaultHeight'];


		$embed = 'swfobject.embedSWF("' . $conf['player'] . '", "' . $replaceElementIdString . '", "' . $width . '", "' . $height . '",
		 		"' . $flashVersion . '", "' . $installUrl . '", ' . $conf['embedParams'] . ');';

		$content = $layout . '
			<script type="text/javascript">
				' . $flashvars . '
				' . $params . '
				' . $attributes . '
				' . $embed . '
			</script>';

		return $content;
	}

	/**
	 * Rendering the cObject, QTOBJECT
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 */
	public function QTOBJECT($conf) {
		$content = '';
		$params = '';
		$prefix = '';
		if ($GLOBALS['TSFE']->baseUrl) {
			$prefix = $GLOBALS['TSFE']->baseUrl;
		}
		if ($GLOBALS['TSFE']->absRefPrefix) {
			$prefix = $GLOBALS['TSFE']->absRefPrefix;
		}

		$filename = $this->stdWrap($conf['file'],$conf['file.']);

		$typeConf = $conf[$conf['type'] . '.'];

			//add QTobject js-file
		$GLOBALS['TSFE']->getPageRenderer()->addJsFile(TYPO3_mainDir . 'contrib/flashmedia/qtobject/qtobject.js');
		$replaceElementIdString = uniqid('mmqt');
		$GLOBALS['TSFE']->register['MMQTID'] = $replaceElementIdString;
		$qtObject = 'QTObject' . $replaceElementIdString;

			// merge with default parameters
		$conf['params.'] = array_merge((array) $typeConf['default.']['params.'], (array) $conf['params.']);

		if (is_array($conf['params.'])) {
			t3lib_div::remapArrayKeys($conf['params.'], $typeConf['mapping.']['params.']);
			foreach ($conf['params.'] as $key => $value) {
				$params .= $qtObject . '.addParam("' .$key . '", "' . $value . '");' . LF;
			}
		}
		$params = ($params ? substr($params, 0, -2) : '') . LF . $qtObject . '.write("' . $replaceElementIdString . '");';

		$alternativeContent = $this->stdWrap($conf['alternativeContent'], $conf['alternativeContent.']);
		$layout = $this->stdWrap($conf['layout'], $conf['layout.']);
		$layout = str_replace('###ID###', $replaceElementIdString, $layout);
		$layout = str_replace('###QTOBJECT###', '<div id="' . $replaceElementIdString . '">' . $alternativeContent . '</div>', $layout);

		$width = $this->stdWrap($conf['width'], $conf['width.']);
		$height = $this->stdWrap($conf['height'], $conf['height.']);
		$width = $width ? $width : $conf[$conf['type'] . '.']['defaultWidth'];
		$height = $height ? $height : $conf[$conf['type'] . '.']['defaultHeight'];

		$embed = 'var ' . $qtObject . ' = new QTObject("' . $prefix . $filename . '", "' . $replaceElementIdString . '", "' . $width . '", "' . $height . '");';

		$content = $layout . '
			<script type="text/javascript">
				' . $embed . '
				' . $params . '
			</script>';

		return $content;
	}









	/************************************
	 *
	 * Various helper functions for content objects:
	 *
	 ************************************/


	/**
	 * Converts a given config in Flexform to a conf-Array
	 * @param	string 		Flexform data
	 * @param	array 		Array to write the data into, by reference
	 * @param	boolean		is set if called recursive. Don't call function with this parameter, it's used inside the function only
	 * @access 	public
	 *
	 */
	public function readFlexformIntoConf($flexData, &$conf, $recursive=FALSE) {
		if ($recursive === FALSE) {
			$flexData = t3lib_div::xml2array($flexData, 'T3');
		}

		if (is_array($flexData)) {
			if (isset($flexData['data']['sDEF']['lDEF'])) {
				$flexData = $flexData['data']['sDEF']['lDEF'];
			}

			foreach ($flexData as $key => $value) {
				if (is_array($value['el']) && count($value['el']) > 0) {
					foreach ($value['el'] as $ekey => $element) {
						if (isset($element['vDEF'])) {
							$conf[$ekey] =  $element['vDEF'];
						} else {
							if(is_array($element)) {
								$this->readFlexformIntoConf($element, $conf[$key][key($element)][$ekey], TRUE);
							} else {
								$this->readFlexformIntoConf($element, $conf[$key][$ekey], TRUE);
							}
						}
					}
				} else {
					$this->readFlexformIntoConf($value['el'], $conf[$key], TRUE);
				}
				if ($value['vDEF']) {
					$conf[$key] = $value['vDEF'];
				}
			}
		}
	}


	/**
	 * Returns all parents of the given PID (Page UID) list
	 *
	 * @param	string		A list of page Content-Element PIDs (Page UIDs) / stdWrap
	 * @param	array		stdWrap array for the list
	 * @return	string		A list of PIDs
	 * @access private
	 */
	function getSlidePids($pidList, $pidConf)	{
		$pidList = trim($this->stdWrap($pidList,$pidConf));
		if (!strcmp($pidList,''))	{
			$pidList = 'this';
		}
		if (trim($pidList))	{
			$listArr = t3lib_div::intExplode(',',str_replace('this',$GLOBALS['TSFE']->contentPid,$pidList));
			$listArr = $this->checkPidArray($listArr);
		}
		$pidList = array();
		if (is_array($listArr)&&count($listArr))	{
			foreach ($listArr as $uid)	{
				$page = $GLOBALS['TSFE']->sys_page->getPage($uid);
				if (!$page['is_siteroot'])	{
					$pidList[] = $page['pid'];
				}
			}
		}
		return implode(',', $pidList);
	}

	/**
	 * Returns a default value for a form field in the FORM cObject.
	 * Page CANNOT be cached because that would include the inserted value for the current user.
	 *
	 * @param	boolean		If noValueInsert OR if the no_cache flag for this page is NOT set, the original default value is returned.
	 * @param	string		$fieldName: The POST var name to get default value for
	 * @param	string		$defaultVal: The current default value
	 * @return	string		The default value, either from INPUT var or the current default, based on whether caching is enabled or not.
	 * @access private
	 */
	function getFieldDefaultValue($noValueInsert, $fieldName, $defaultVal) {
		if (!$GLOBALS['TSFE']->no_cache || (!isset($_POST[$fieldName]) && !isset($_GET[$fieldName])) || $noValueInsert)	{
			return $defaultVal;
		} else {
			return t3lib_div::_GP($fieldName);
		}
	}

	/**
	 * Returns a <img> tag with the image file defined by $file and processed according to the properties in the TypoScript array.
	 * Mostly this function is a sub-function to the IMAGE function which renders the IMAGE cObject in TypoScript. This function is called by "$this->cImage($conf['file'],$conf);" from IMAGE().
	 *
	 * @param	string		File TypoScript resource
	 * @param	array		TypoScript configuration properties
	 * @return	string		<img> tag, (possibly wrapped in links and other HTML) if any image found.
	 * @access private
	 * @see IMAGE()
	 */
	function cImage($file,$conf) {
		$info = $this->getImgResource($file,$conf['file.']);
		$GLOBALS['TSFE']->lastImageInfo=$info;
		if (is_array($info))	{
			$info[3] = t3lib_div::png_to_gif_by_imagemagick($info[3]);
			$GLOBALS['TSFE']->imagesOnPage[]=$info[3];		// This array is used to collect the image-refs on the page...

			if (!strlen($conf['altText']) && !is_array($conf['altText.']))	{	// Backwards compatible:
				if ($conf['altText'] || $conf['altText.']) {
					$GLOBALS['TSFE']->logDeprecatedTyposcript('IMAGE.alttext');
				}
				$conf['altText'] = $conf['alttext'];
				$conf['altText.'] = $conf['alttext.'];
			}
			$altParam = $this->getAltParam($conf);
			$theValue = '<img src="'.htmlspecialchars($GLOBALS['TSFE']->absRefPrefix.t3lib_div::rawUrlEncodeFP($info[3])).'" width="'.$info[0].'" height="'.$info[1].'"'.$this->getBorderAttr(' border="'.intval($conf['border']).'"').(($conf['params'] || is_array($conf['params.']))?' '.$this->stdWrap($conf['params'],$conf['params.']):'').($altParam).' />';
			if ($conf['linkWrap'])	{
				$theValue = $this->linkWrap($theValue,$conf['linkWrap']);
			} elseif ($conf['imageLinkWrap']) {
				$theValue = $this->imageLinkWrap($theValue,$info['origFile'],$conf['imageLinkWrap.']);
			}
			return $this->wrap($theValue,$conf['wrap']);
		}
	}

	/**
	 * Returns the 'border' attribute for an <img> tag only if the doctype is not xhtml_strict,xhtml_11 or xhtml_2 or if the config parameter 'disableImgBorderAttr' is not set.
	 *
	 * @param	string		the border attribute
	 * @return	string		the border attribute
	 */
	function getBorderAttr($borderAttr) {
		if (!t3lib_div::inList('xhtml_strict,xhtml_11,xhtml_2',$GLOBALS['TSFE']->xhtmlDoctype) && !$GLOBALS['TSFE']->config['config']['disableImgBorderAttr']) {
			return $borderAttr;
		}
	}

	/**
	 * Wraps the input string in link-tags that opens the image in a new window.
	 *
	 * @param	string		String to wrap, probably an <img> tag
	 * @param	string		The original image file
	 * @param	array		TypoScript properties for the "imageLinkWrap" function
	 * @return	string		The input string, $string, wrapped as configured.
	 * @see cImage()
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=316&cHash=2848266da6
	 */
	function imageLinkWrap($string,$imageFile,$conf) {
		$a1='';
		$a2='';
		$content=$string;
		if ($this->stdWrap($conf['enable'],$conf['enable.']))	{
			$content=$this->typolink($string, $conf['typolink.']);
			$imageFile = $this->stdWrap($imageFile, $conf['file.']);

				// imageFileLink:
			if ($content==$string && @is_file($imageFile)) {
				$params = '';
				if ($conf['width']) {$params.='&width='.rawurlencode($conf['width']);}
				if ($conf['height']) {$params.='&height='.rawurlencode($conf['height']);}
				if ($conf['effects']) {$params.='&effects='.rawurlencode($conf['effects']);}
				if ($conf['sample']) {$params.='&sample=1';}
				if ($conf['alternativeTempPath']) {$params.='&alternativeTempPath='.rawurlencode($conf['alternativeTempPath']);}

				if ($conf['bodyTag']) {$params.='&bodyTag='.rawurlencode($conf['bodyTag']);}
				if ($conf['title']) {$params.='&title='.rawurlencode($conf['title']);}
				if ($conf['wrap']) {$params.='&wrap='.rawurlencode($conf['wrap']);}

				$md5_value = md5(
						$imageFile.'|'.
						$conf['width'].'|'.
						$conf['height'].'|'.
						$conf['effects'].'|'.
						$conf['bodyTag'].'|'.
						$conf['title'].'|'.
						$conf['wrap'].'|'.
						$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'].'|');

				$params.= '&md5='.$md5_value;
				$url = $GLOBALS['TSFE']->absRefPrefix.'index.php?eID=tx_cms_showpic&file='.rawurlencode($imageFile).$params;
				if ($conf['JSwindow.']['altUrl'] || $conf['JSwindow.']['altUrl.'])	{
					$altUrl = $this->stdWrap($conf['JSwindow.']['altUrl'], $conf['JSwindow.']['altUrl.']);
					if ($altUrl)	{
						$url = $altUrl . ($conf['JSwindow.']['altUrl_noDefaultParams'] ? '' : '?file='.rawurlencode($imageFile).$params);
					}
				}

					// Create TARGET-attribute only if the right doctype is used
				if (!t3lib_div::inList('xhtml_strict,xhtml_11,xhtml_2', $GLOBALS['TSFE']->xhtmlDoctype))	{
					if (isset($conf['target']))	{
						$target = sprintf(' target="%s"', $conf['target']);
					} else {
						$target = ' target="thePicture"';
					}
				} else {
					$target = '';
				}

				if ($conf['JSwindow'])	{
					$gifCreator = t3lib_div::makeInstance('tslib_gifbuilder');
					$gifCreator->init();
					$gifCreator->mayScaleUp = 0;
					$dims = $gifCreator->getImageScale($gifCreator->getImageDimensions($imageFile),$conf['width'],$conf['height'],'');
					$offset = t3lib_div::intExplode(',',$conf['JSwindow.']['expand'].',');

					$a1='<a href="'. htmlspecialchars($url) .'" onclick="'.
						htmlspecialchars('openPic(\''.$GLOBALS['TSFE']->baseUrlWrap($url).'\',\''.($conf['JSwindow.']['newWindow']?md5($url):'thePicture').'\',\'width='.($dims[0]+$offset[0]).',height='.($dims[1]+$offset[1]).',status=0,menubar=0\'); return false;').
						'"'.$target.$GLOBALS['TSFE']->ATagParams.'>';
					$a2='</a>';
					$GLOBALS['TSFE']->setJS('openPic');
				} else {
					$a1='<a href="'.htmlspecialchars($url).'"'.$target.$GLOBALS['TSFE']->ATagParams.'>';
					$a2='</a>';
				}

				$string = $this->stdWrap($string,$conf['stdWrap.']);

				$content=$a1.$string.$a2;
			}
		}

		return $content;
	}

	/**
	 * Returns content of a file. If it's an image the content of the file is not returned but rather an image tag is.
	 *
	 * @param	string		The filename, being a TypoScript resource data type
	 * @param	string		Additional parameters (attributes). Default is empty alt and title tags.
	 * @return	string		If jpg,gif,jpeg,png: returns image_tag with picture in. If html,txt: returns content string
	 * @see FILE()
	 */
	function fileResource($fName, $addParams='alt="" title=""')	{
		$incFile = $GLOBALS['TSFE']->tmpl->getFileName($fName);
		if ($incFile)	{
			$fileinfo = t3lib_div::split_fileref($incFile);
			if (t3lib_div::inList('jpg,gif,jpeg,png',$fileinfo['fileext']))	{
				$imgFile = $incFile;
				$imgInfo = @getImageSize($imgFile);
				return '<img src="'.$GLOBALS['TSFE']->absRefPrefix.$imgFile.'" width="'.$imgInfo[0].'" height="'.$imgInfo[1].'"'.$this->getBorderAttr(' border="0"').' '.$addParams.' />';
			} elseif (filesize($incFile)<1024*1024) {
				return $GLOBALS['TSFE']->tmpl->fileContent($incFile);
			}
		}
	}

	/**
	 * Sets the SYS_LASTCHANGED timestamp if input timestamp is larger than current value.
	 * The SYS_LASTCHANGED timestamp can be used by various caching/indexing applications to determine if the page has new content.
	 * Therefore you should call this function with the last-changed timestamp of any element you display.
	 *
	 * @param	integer		Unix timestamp (number of seconds since 1970)
	 * @return	void
	 * @see tslib_fe::setSysLastChanged()
	 */
	function lastChanged($tstamp)	{
		$tstamp = intval($tstamp);
		if ($tstamp>intval($GLOBALS['TSFE']->register['SYS_LASTCHANGED']))	{
			$GLOBALS['TSFE']->register['SYS_LASTCHANGED'] = $tstamp;
		}
	}

	/**
	 * Wraps the input string by the $wrap value and implements the "linkWrap" data type as well.
	 * The "linkWrap" data type means that this function will find any integer encapsulated in {} (curly braces) in the first wrap part and substitute it with the corresponding page uid from the rootline where the found integer is pointing to the key in the rootline. See link below.
	 *
	 * @param	string		Input string
	 * @param	string		A string where the first two parts separated by "|" (vertical line) will be wrapped around the input string
	 * @return	string		Wrapped output string
	 * @see wrap(), cImage(), FILE()
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=282&cHash=831a95115d
	 */
	function linkWrap($content,$wrap)	{
		$wrapArr = explode('|', $wrap);
		if (preg_match('/\{([0-9]*)\}/',$wrapArr[0],$reg))	{
			if ($uid = $GLOBALS['TSFE']->tmpl->rootLine[$reg[1]]['uid'])	{
				$wrapArr[0] = str_replace($reg[0],$uid,$wrapArr[0]);
			}
		}
		return trim($wrapArr[0]).$content.trim($wrapArr[1]);
	}

	/**
	 * An abstraction method which creates an alt or title parameter for an HTML img, applet, area or input element and the FILE content element.
	 * From the $conf array it implements the properties "altText", "titleText" and "longdescURL"
	 *
	 * @param	array		TypoScript configuration properties
	 * @param	boolean		If set, the longdesc attribute will be generated - must only be used for img elements!
	 * @return	string		Parameter string containing alt and title parameters (if any)
	 * @see IMGTEXT(), FILE(), FORM(), cImage(), filelink()
	 */
	function getAltParam($conf, $longDesc=true)	{
		$altText = trim($this->stdWrap($conf['altText'], $conf['altText.']));
		$titleText = trim($this->stdWrap($conf['titleText'],$conf['titleText.']));
		$longDesc = trim($this->stdWrap($conf['longdescURL'],$conf['longdescURL.']));

			// "alt":
		$altParam = ' alt="'.htmlspecialchars($altText).'"';

			// "title":
		$emptyTitleHandling = 'useAlt';
		if ($conf['emptyTitleHandling'])	{
				// choices: 'keepEmpty' | 'useAlt' | 'removeAttr'
			$emptyTitleHandling = $conf['emptyTitleHandling'];
		}
		if ($titleText || $emptyTitleHandling == 'keepEmpty')	{
			$altParam.= ' title="'.htmlspecialchars($titleText).'"';
		} elseif (!$titleText && $emptyTitleHandling == 'useAlt')	{
			$altParam.= ' title="'.htmlspecialchars($altText).'"';
		}

			// "longDesc" URL
		if ($longDesc)	{
			$altParam.= ' longdesc="'.htmlspecialchars(strip_tags($longDesc)).'"';
		}

		return $altParam;
	}

	/**
	 * Removes forbidden characters and spaces from name/id attributes in the form tag and formfields
	 *
	 * @param	string		Input string
	 * @return	string		the cleaned string
	 * @see FORM()
	 */
	function cleanFormName($name) {
			// turn data[x][y] into data:x:y:
		$name = preg_replace('/\[|\]\[?/',':',trim($name));
			// remove illegal chars like _
		return preg_replace('#[^:a-zA-Z0-9]#','',$name);
	}

	/**
	 * An abstraction method to add parameters to an A tag.
	 * Uses the ATagParams property.
	 *
	 * @param	array		TypoScript configuration properties
	 * @param	boolean		If set, will add the global config.ATagParams to the link
	 * @return	string		String containing the parameters to the A tag (if non empty, with a leading space)
	 * @see IMGTEXT(), filelink(), makelinks(), typolink()
	 */
	 function getATagParams($conf, $addGlobal=1)	{
		$aTagParams = '';
		if ($conf['ATagParams.'])	{
			$aTagParams = ' '.$this->stdWrap($conf['ATagParams'], $conf['ATagParams.']);
		} elseif ($conf['ATagParams'])	{
			$aTagParams = ' '.$conf['ATagParams'];
		}
		if ($addGlobal)	{
			$aTagParams = ' '.trim($GLOBALS['TSFE']->ATagParams.$aTagParams);
		}
		return $aTagParams;
	 }

	/**
	 * All extension links should ask this function for additional properties to their tags.
	 * Designed to add for instance an "onclick" property for site tracking systems.
	 *
	 * @param	string	URL of the website
	 * @return  string	the additional tag properties
	 */
	function extLinkATagParams($URL, $TYPE)	{
		$out = '';

		if ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['extLinkATagParamsHandler']) {
			$extLinkATagParamsHandler = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['extLinkATagParamsHandler']);

			if(method_exists($extLinkATagParamsHandler, 'main')) {
				$out.= trim($extLinkATagParamsHandler->main($URL, $TYPE, $this));
			}
		}

		return trim($out) ? ' '.trim($out) : '' ;
	}

















	/***********************************************
	 *
	 * HTML template processing functions
	 *
	 ***********************************************/

	/**
	 * Returns a subpart from the input content stream.
	 * A subpart is a part of the input stream which is encapsulated in a
	 * string matching the input string, $marker. If this string is found
	 * inside of HTML comment tags the start/end points of the content block
	 * returned will be that right outside that comment block.
	 * Example: The contennt string is
	 * "Hello <!--###sub1### begin--> World. How are <!--###sub1### end--> you?"
	 * If $marker is "###sub1###" then the content returned is
	 * " World. How are ". The input content string could just as well have
	 * been "Hello ###sub1### World. How are ###sub1### you?" and the result
	 * would be the same
	 * Wrapper for t3lib_parsehtml::getSubpart which behaves identical
	 *
	 * @param	string		The content stream, typically HTML template content.
	 * @param	string		The marker string, typically on the form "###[the marker string]###"
	 * @return	string		The subpart found, if found.
	 * @see substituteSubpart(), t3lib_parsehtml::getSubpart()
	 */
	public function getSubpart($content, $marker) {
		return t3lib_parsehtml::getSubpart($content, $marker);
	}

	/**
	 * Substitute subpart in input template stream.
	 * This function substitutes a subpart in $content with the content of
	 * $subpartContent.
	 * Wrapper for t3lib_parsehtml::substituteSubpart which behaves identical
	 *
	 * @param	string		The content stream, typically HTML template content.
	 * @param	string		The marker string, typically on the form "###[the marker string]###"
	 * @param	mixed		The content to insert instead of the subpart found. If a string, then just plain substitution happens (includes removing the HTML comments of the subpart if found). If $subpartContent happens to be an array, it's [0] and [1] elements are wrapped around the EXISTING content of the subpart (fetched by getSubpart()) thereby not removing the original content.
	 * @param	boolean		If $recursive is set, the function calls itself with the content set to the remaining part of the content after the second marker. This means that proceding subparts are ALSO substituted!
	 * @return	string		The processed HTML content string.
	 * @see getSubpart(), t3lib_parsehtml::substituteSubpart()
	 */
	public function substituteSubpart($content, $marker, $subpartContent, $recursive = 1) {
		return t3lib_parsehtml::substituteSubpart(
			$content,
			$marker,
			$subpartContent,
			$recursive
		);
	}

	/**
	 * Substitues multiple subparts at once
	 *
	 * @param	string		The content stream, typically HTML template content.
	 * @param	array		The array of key/value pairs being subpart/content values used in the substitution. For each element in this array the function will substitute a subpart in the content stream with the content.
	 * @return	string		The processed HTML content string.
	 */
	public function substituteSubpartArray($content, array $subpartsContent) {
		return t3lib_parsehtml::substituteSubpartArray(
			$content,
			$subpartsContent
		);
	}

	/**
	 * Substitutes a marker string in the input content
	 * (by a simple str_replace())
	 *
	 * @param	string		The content stream, typically HTML template content.
	 * @param	string		The marker string, typically on the form "###[the marker string]###"
	 * @param	mixed		The content to insert instead of the marker string found.
	 * @return	string		The processed HTML content string.
	 * @see substituteSubpart()
	 */
	public function substituteMarker($content, $marker, $markContent) {
		return t3lib_parsehtml::substituteMarker(
			$content,
			$marker,
			$markContent
		);
	}

	/**
	 * Multi substitution function with caching.
	 *
	 * This function should be a one-stop substitution function for working
	 * with HTML-template. It does not substitute by str_replace but by
	 * splitting. This secures that the value inserted does not themselves
	 * contain markers or subparts.
	 *
	 * Note that the "caching" won't cache the content of the substition,
	 * but only the splitting of the template in various parts. So if you
	 * want only one cache-entry per template, make sure you always pass the
	 * exact same set of marker/subpart keys. Else you will be flooding the
	 * users cache table.
	 *
	 * This function takes three kinds of substitutions in one:
	 * $markContentArray is a regular marker-array where the 'keys' are
	 * substituted in $content with their values
	 *
	 * $subpartContentArray works exactly like markContentArray only is whole
	 * subparts substituted and not only a single marker.
	 *
	 * $wrappedSubpartContentArray is an array of arrays with 0/1 keys where
	 * the subparts pointed to by the main key is wrapped with the 0/1 value
	 * alternating.
	 *
	 * @param	string		The content stream, typically HTML template content.
	 * @param	array		Regular marker-array where the 'keys' are substituted in $content with their values
	 * @param	array		Exactly like markContentArray only is whole subparts substituted and not only a single marker.
	 * @param	array		An array of arrays with 0/1 keys where the subparts pointed to by the main key is wrapped with the 0/1 value alternating.
	 * @return	string		The output content stream
	 * @see substituteSubpart(), substituteMarker(), substituteMarkerInObject(), TEMPLATE()
	 */
	public function substituteMarkerArrayCached($content, array $markContentArray = NULL, array $subpartContentArray = NULL, array $wrappedSubpartContentArray = NULL) {
		$GLOBALS['TT']->push('substituteMarkerArrayCached');

			// If not arrays then set them
		if (is_null($markContentArray))	$markContentArray=array();	// Plain markers
		if (is_null($subpartContentArray))	$subpartContentArray=array();	// Subparts being directly substituted
		if (is_null($wrappedSubpartContentArray))	$wrappedSubpartContentArray=array();	// Subparts being wrapped
			// Finding keys and check hash:
		$sPkeys = array_keys($subpartContentArray);
		$wPkeys = array_keys($wrappedSubpartContentArray);
		$aKeys = array_merge(array_keys($markContentArray),$sPkeys,$wPkeys);
		if (!count($aKeys))	{
			$GLOBALS['TT']->pull();
			return $content;
		}
		asort($aKeys);
		$storeKey = md5('substituteMarkerArrayCached_storeKey:'.serialize(array($content,$aKeys)));
		if ($this->substMarkerCache[$storeKey])	{
			$storeArr = $this->substMarkerCache[$storeKey];
			$GLOBALS['TT']->setTSlogMessage('Cached',0);
		} else {
			$storeArrDat = $GLOBALS['TSFE']->sys_page->getHash($storeKey);
			if (!isset($storeArrDat))	{
					// Initialize storeArr
				$storeArr=array();

					// Finding subparts and substituting them with the subpart as a marker
				foreach ($sPkeys as $sPK) {
					$content =$this->substituteSubpart($content,$sPK,$sPK);
				}

					// Finding subparts and wrapping them with markers
				foreach ($wPkeys as $wPK) {
					$content =$this->substituteSubpart($content,$wPK,array($wPK,$wPK));
				}

					// traverse keys and quote them for reg ex.
				foreach ($aKeys as $tK => $tV) {
					$aKeys[$tK] = preg_quote($tV, '/');
				}
				$regex = '/' . implode('|', $aKeys) . '/';
					// Doing regex's
				$storeArr['c'] = preg_split($regex, $content);
				preg_match_all($regex, $content, $keyList);
				$storeArr['k']=$keyList[0];
					// Setting cache:
				$this->substMarkerCache[$storeKey] = $storeArr;

					// Storing the cached data:
				$GLOBALS['TSFE']->sys_page->storeHash($storeKey, serialize($storeArr), 'substMarkArrayCached');

				$GLOBALS['TT']->setTSlogMessage('Parsing',0);
			} else {
					// Unserializing
				$storeArr = unserialize($storeArrDat);
					// Setting cache:
				$this->substMarkerCache[$storeKey] = $storeArr;
				$GLOBALS['TT']->setTSlogMessage('Cached from DB',0);
			}
		}

			// Substitution/Merging:
			// Merging content types together, resetting
		$valueArr = array_merge($markContentArray,$subpartContentArray,$wrappedSubpartContentArray);

		$wSCA_reg=array();
		$content = '';
			// traversing the keyList array and merging the static and dynamic content
		foreach ($storeArr['k'] as $n => $keyN) {
			$content.=$storeArr['c'][$n];
			if (!is_array($valueArr[$keyN]))	{
				$content.=$valueArr[$keyN];
			} else {
				$content.=$valueArr[$keyN][(intval($wSCA_reg[$keyN])%2)];
				$wSCA_reg[$keyN]++;
			}
		}
		$content.=$storeArr['c'][count($storeArr['k'])];

		$GLOBALS['TT']->pull();
		return $content;
	}

	/**
	 * Traverses the input $markContentArray array and for each key the marker
	 * by the same name (possibly wrapped and in upper case) will be
	 * substituted with the keys value in the array.
	 *
	 * This is very useful if you have a data-record to substitute in some
	 * content. In particular when you use the $wrap and $uppercase values to
	 * pre-process the markers. Eg. a key name like "myfield" could effectively
	 * be represented by the marker "###MYFIELD###" if the wrap value
	 * was "###|###" and the $uppercase boolean true.
	 *
	 * @param	string		The content stream, typically HTML template content.
	 * @param	array		The array of key/value pairs being marker/content values used in the substitution. For each element in this array the function will substitute a marker in the content stream with the content.
	 * @param	string		A wrap value - [part 1] | [part 2] - for the markers before substitution
	 * @param	boolean		If set, all marker string substitution is done with upper-case markers.
	 * @param	boolean		If set, all unused marker are deleted.
	 * @return	string		The processed output stream
	 * @see substituteMarker(), substituteMarkerInObject(), TEMPLATE()
	 */
	public function substituteMarkerArray($content, array $markContentArray, $wrap = '', $uppercase = false, $deleteUnused = false) {
		return t3lib_parsehtml::substituteMarkerArray($content, $markContentArray, $wrap, $uppercase, $deleteUnused);
	}

	/**
	 * Substitute marker array in an array of values
	 *
	 * @param	mixed		If string, then it just calls substituteMarkerArray. If array (and even multi-dim) then for each key/value pair the marker array will be substituted (by calling this function recursively)
	 * @param	array		The array of key/value pairs being marker/content values used in the substitution. For each element in this array the function will substitute a marker in the content string/array values.
	 * @return	mixed		The processed input variable.
	 * @see substituteMarker()
	 */
	public function substituteMarkerInObject(&$tree, array $markContentArray) {
		if (is_array ($tree)) {
			foreach ($tree as $key => $value) {
				$this->substituteMarkerInObject ($tree[$key], $markContentArray);
			}
		} else {
			$tree = $this->substituteMarkerArray($tree, $markContentArray);
		}

		return $tree;
	}

	/**
	 * Adds elements to the input $markContentArray based on the values from
	 * the fields from $fieldList found in $row
	 *
	 * @param	array		Array with key/values being marker-strings/substitution values.
	 * @param	array		An array with keys found in the $fieldList (typically a record) which values should be moved to the $markContentArray
	 * @param	string		A list of fields from the $row array to add to the $markContentArray array. If empty all fields from $row will be added (unless they are integers)
	 * @param	boolean		If set, all values added to $markContentArray will be nl2br()'ed
	 * @param	string		Prefix string to the fieldname before it is added as a key in the $markContentArray. Notice that the keys added to the $markContentArray always start and end with "###"
	 * @param	boolean		If set, all values are passed through htmlspecialchars() - RECOMMENDED to avoid most obvious XSS and maintain XHTML compliance.
	 * @return	array		The modified $markContentArray
	 */
	public function fillInMarkerArray(array $markContentArray, array $row, $fieldList = '', $nl2br = true, $prefix = 'FIELD_', $HSC = false) {
		if ($fieldList) {
			$fArr = t3lib_div::trimExplode(',', $fieldList, 1);
			foreach ($fArr as $field) {
				$markContentArray['###' . $prefix . $field . '###'] = $nl2br ? nl2br($row[$field]) : $row[$field];
			}
		} else {
			if (is_array($row)) {
				foreach ($row as $field => $value) {
					if (!t3lib_div::testInt($field)) {
						if ($HSC) {
							$value = htmlspecialchars($value);
						}

						$markContentArray['###' . $prefix . $field . '###'] = $nl2br ? nl2br($value) : $value;
					}
				}
			}
		}

		return $markContentArray;
	}

























	/***********************************************
	 *
	 * "stdWrap" + sub functions
	 *
	 ***********************************************/


	/**
	 * The "stdWrap" function. This is the implementation of what is known as "stdWrap properties" in TypoScript.
	 * Basically "stdWrap" performs some processing of a value based on properties in the input $conf array (holding the TypoScript "stdWrap properties")
	 * See the link below for a complete list of properties and what they do. The order of the table with properties found in TSref (the link) follows the actual order of implementation in this function.
	 *
	 * If $this->alternativeData is an array it's used instead of the $this->data array in ->getData
	 *
	 * @param	string		Input value undergoing processing in this function. Possibly substituted by other values fetched from another source.
	 * @param	array		TypoScript "stdWrap properties".
	 * @return	string		The processed input value
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=314&cHash=02ab044c7b
	 */
	function stdWrap($content,$conf) {
		$this->getLinkChecker()->check($conf, 'stdWrap');
		return parent::stdWrap($content, $conf);
	}

	/**
	 * Returns number of rows selected by the query made by the properties set.
	 * Implements the stdWrap "numRows" property
	 *
	 * @param	array		TypoScript properties for the property (see link to "numRows")
	 * @return	integer		The number of rows found by the select (FALSE on error)
	 * @access private
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=317&cHash=e28e53e634
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=318&cHash=a98cb4e7e6
	 * @see stdWrap()
	 */
	function numRows($conf)	{
		$result = FALSE;
		$conf['select.']['selectFields'] = 'count(*)';

		$res = $this->exec_getQuery($conf['table'],$conf['select.']);

		if ($error = $GLOBALS['TYPO3_DB']->sql_error())	{
			$GLOBALS['TT']->setTSlogMessage($error,3);
		} else {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
			$result = intval($row[0]);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $result;
	}

	/**
	 * Compares values together based on the settings in the input TypoScript array and returns true or false based on the comparison result.
	 * Implements the "if" function in TYPO3 TypoScript
	 *
	 * @param	array		TypoScript properties defining what to compare
	 * @return	boolean
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=320&cHash=da01618eab
	 * @see HMENU(), CASEFUNC(), IMAGE(), COLUMN(), stdWrap(), _parseFunc()
	 */
	function checkIf($conf)	{
		if (!is_array($conf))	{return true;}
		if (isset($conf['directReturn']))	{return $conf['directReturn'] ? 1 : 0;}
		$flag = true;
			if (isset($conf['isTrue']) || isset($conf['isTrue.']))	{
				$isTrue = trim($this->stdWrap($conf['isTrue'],$conf['isTrue.']));
				if (!$isTrue)	{
					$flag=0;
				}
			}
			if (isset($conf['isFalse']) || isset($conf['isFalse.']))	{
				$isFalse = trim($this->stdWrap($conf['isFalse'],$conf['isFalse.']));
				if ($isFalse)	{
					$flag=0;
				}
			}
			if (isset($conf['isPositive']) || isset($conf['isPositive.']))	{
				$number = $this->calc($this->stdWrap($conf['isPositive'],$conf['isPositive.']));
				if ($number<1)	{
					$flag=0;
				}
			}
			if ($flag)	{
				$value = trim($this->stdWrap($conf['value'],$conf['value.']));

				if (isset($conf['isGreaterThan']) || isset($conf['isGreaterThan.']))	{
					$number = trim($this->stdWrap($conf['isGreaterThan'],$conf['isGreaterThan.']));
					if ($number<=$value)	{
						$flag=0;
					}
				}
				if (isset($conf['isLessThan']) || isset($conf['isLessThan.']))	{
					$number = trim($this->stdWrap($conf['isLessThan'],$conf['isLessThan.']));
					if ($number>=$value)	{
						$flag=0;
					}
				}
				if (isset($conf['equals']) || isset($conf['equals.']))	{
					$number = trim($this->stdWrap($conf['equals'],$conf['equals.']));
					if ($number!=$value)	{
						$flag=0;
					}
				}
				if (isset($conf['isInList']) || isset($conf['isInList.']))	{
					$number = trim($this->stdWrap($conf['isInList'],$conf['isInList.']));
					if (!t3lib_div::inList($value,$number))	{
						$flag=0;
					}
				}
			}
		if ($conf['negate'])	{$flag = $flag ? 0 : 1;}
		return $flag;
	}

	/**
	 * Reads a directory for files and returns the filepaths in a string list separated by comma.
	 * Implements the stdWrap property "filelist"
	 *
	 * @param	string		The command which contains information about what files/directory listing to return. See the "filelist" property of stdWrap for details.
	 * @return	string		Comma list of files.
	 * @access private
	 * @see stdWrap()
	 */
	function filelist($data)	{
		$data = trim($data);
		if ($data)	{
			$data_arr = explode('|',$data);
				// read directory:
			if ($GLOBALS['TSFE']->lockFilePath)	{		// MUST exist!
				$path = $this->clean_directory($data_arr[0]);	// Cleaning name..., only relative paths accepted.
				// see if path starts with lockFilePath, the additional '/' is needed because clean_directory gets rid of it
				$path = (t3lib_div::isFirstPartOfStr($path . '/', $GLOBALS['TSFE']->lockFilePath) ? $path : '');
			}
			if ($path)	{
				$items = Array('files'=>array(), 'sorting'=>array());
				$ext_list = strtolower(t3lib_div::uniqueList($data_arr[1]));
				$sorting = trim($data_arr[2]);
					// read dir:
				$d = @dir($path);
				$tempArray=Array();
				if (is_object($d))	{
					$count=0;
					while($entry=$d->read()) {
						if ($entry!='.' && $entry!='..')	{
							$wholePath = $path.'/'.$entry;		// Because of odd PHP-error where  <br />-tag is sometimes placed after a filename!!
							if (file_exists($wholePath) && filetype($wholePath)=='file')	{
								$info = t3lib_div::split_fileref($wholePath);
								if (!$ext_list || t3lib_div::inList($ext_list,$info['fileext']))	{
									$items['files'][] = $info['file'];
									switch($sorting)	{
										case 'name':
											$items['sorting'][] = strtolower($info['file']);
										break;
										case 'size':
											$items['sorting'][] = filesize($wholePath);
										break;
										case 'ext':
											$items['sorting'][] = $info['fileext'];
										break;
										case 'date':
											$items['sorting'][] = filectime($wholePath);
										break;
										case 'mdate':
											$items['sorting'][] = filemtime($wholePath);
										break;
										default:
											$items['sorting'][] = $count;
										break;
									}
									$count++;
								}
							}
						}
					}
					$d->close();
				}
					// Sort if required
				if (count($items['sorting']))	{
					if (strtolower(trim($data_arr[3]))!='r')	{
						asort($items['sorting']);
					} else {
						arsort($items['sorting']);
					}
				}
				if (count($items['files']))	{
						// make list
					reset($items['sorting']);
					$fullPath = trim($data_arr[4]);
					$list_arr=Array();
					foreach ($items['sorting'] as $key => $v) {
						$list_arr[]=  $fullPath ? $path.'/'.$items['files'][$key] : $items['files'][$key];
					}
					return implode(',',$list_arr);
				}
			}
		}
	}

	/**
	 * Cleans $theDir for slashes in the end of the string and returns the new path, if it exists on the server.
	 *
	 * @param	string		Absolute path to directory
	 * @return	string		The directory path if it existed as was valid to access.
	 * @access private
	 * @see filelist()
	 */
	function clean_directory($theDir)	{
		if (t3lib_div::validPathStr($theDir))	{		// proceeds if no '//', '..' or '\' is in the $theFile
			$theDir = preg_replace('/[\/\. ]*$/','',$theDir);		// Removes all dots, slashes and spaces after a path...
			if (!t3lib_div::isAbsPath($theDir) && @is_dir($theDir))	{
				return $theDir;
			}
		}
	}

	/**
	 * Passes the input value, $theValue, to an instance of "t3lib_parsehtml" together with the TypoScript options which are first converted from a TS style array to a set of arrays with options for the t3lib_parsehtml class.
	 *
	 * @param	string		The value to parse by the class "t3lib_parsehtml"
	 * @param	array		TypoScript properties for the parser. See link.
	 * @return	string		Return value.
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=330&cHash=664e0296bf
	 * @see stdWrap(), t3lib_parsehtml::HTMLparserConfig(), t3lib_parsehtml::HTMLcleaner()
	 */
	function HTMLparser_TSbridge($theValue, $conf)	{
		$htmlParser = t3lib_div::makeInstance('t3lib_parsehtml');
		$htmlParserCfg =  $htmlParser->HTMLparserConfig($conf);
		return $htmlParser->HTMLcleaner($theValue,$htmlParserCfg[0],$htmlParserCfg[1],$htmlParserCfg[2],$htmlParserCfg[3]);
	}

	/**
	 * Wrapping input value in a regular "wrap" but parses the wrapping value first for "insertData" codes.
	 *
	 * @param	string		Input string being wrapped
	 * @param	string		The wrap string, eg. "<strong></strong>" or more likely here '<a href="index.php?id={TSFE:id}"> | </a>' which will wrap the input string in a <a> tag linking to the current page.
	 * @return	string		Output string wrapped in the wrapping value.
	 * @see insertData(), stdWrap()
	 */
	function dataWrap($content,$wrap)	{
		return $this->wrap($content,$this->insertData($wrap));
	}

	/**
	 * Implements the "insertData" property of stdWrap meaning that if strings matching {...} is found in the input string they will be substituted with the return value from getData (datatype) which is passed the content of the curly braces.
	 * Example: If input string is "This is the page title: {page:title}" then the part, '{page:title}', will be substituted with the current pages title field value.
	 *
	 * @param	string		Input value
	 * @return	string		Processed input value
	 * @see getData(), stdWrap(), dataWrap()
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=314&cHash=02ab044c7b
	 */
	function insertData($str)	{
		$inside=0;
		$newVal='';
		$pointer=0;
		$totalLen = strlen($str);
		do	{
			if (!$inside)	{
				$len = strcspn(substr($str,$pointer),'{');
				$newVal.= substr($str,$pointer,$len);
				$inside = 1;
			} else {
				$len = strcspn(substr($str,$pointer),'}')+1;
				$newVal.= $this->getData(substr($str,$pointer+1,$len-2),$this->data);
				$inside = 0;
			}
			$pointer+=$len;
		} while($pointer<$totalLen);
		return $newVal;
	}

	/**
	 * Returns a HTML comment with the second part of input string (divided by "|") where first part is an integer telling how many trailing tabs to put before the comment on a new line.
	 * Notice; this function (used by stdWrap) can be disabled by a "config.disablePrefixComment" setting in TypoScript.
	 *
	 * @param	string		Input value
	 * @param	array		TypoScript Configuration (not used at this point.)
	 * @param	string		The content to wrap the comment around.
	 * @return	string		Processed input value
	 * @see stdWrap()
	 */
	function prefixComment($str,$conf,$content)	{
		$parts = explode('|',$str);

		$output =
			LF.str_pad('',$parts[0],TAB).
			'<!-- '.htmlspecialchars($this->insertData($parts[1])).' [begin] -->'.
			LF.str_pad('',$parts[0]+1,TAB).
				$content.
			LF.str_pad('',$parts[0],TAB).
			'<!-- '.htmlspecialchars($this->insertData($parts[1])).' [end] -->'.
			LF.str_pad('',$parts[0]+1,TAB);

		return $output;
	}

	/**
	 * Implements the stdWrap property "substring" which is basically a TypoScript implementation of the PHP function, substr()
	 *
	 * @param	string		The string to perform the operation on
	 * @param	string		The parameters to substring, given as a comma list of integers where the first and second number is passed as arg 1 and 2 to substr().
	 * @return	string		The processed input value.
	 * @access private
	 * @see stdWrap()
	 */
	function substring($content,$options)	{
		$options = t3lib_div::intExplode(',',$options.',');
		if ($options[1])	{
			return $GLOBALS['TSFE']->csConvObj->substr($GLOBALS['TSFE']->renderCharset,$content,$options[0],$options[1]);
		} else {
			return $GLOBALS['TSFE']->csConvObj->substr($GLOBALS['TSFE']->renderCharset,$content,$options[0]);
		}
	}

	/**
	 * Implements the stdWrap property "crop" which is a modified "substr" function allowing to limit a string lenght to a certain number of chars (from either start or end of string) and having a pre/postfix applied if the string really was cropped.
	 *
	 * @param	string		The string to perform the operation on
	 * @param	string		The parameters splitted by "|": First parameter is the max number of chars of the string. Negative value means cropping from end of string. Second parameter is the pre/postfix string to apply if cropping occurs. Third parameter is a boolean value. If set then crop will be applied at nearest space.
	 * @return	string		The processed input value.
	 * @access private
	 * @see stdWrap()
	 */
	function crop($content,$options)	{
		$options = explode('|',$options);
		$chars = intval($options[0]);
		$afterstring = trim($options[1]);
		$crop2space = trim($options[2]);
		if ($chars)	{
			if (strlen($content)>abs($chars))	{
				if ($chars<0)	{
					$content = $GLOBALS['TSFE']->csConvObj->substr($GLOBALS['TSFE']->renderCharset,$content,$chars);
					$trunc_at = strpos($content, ' ');
					$content = ($trunc_at&&$crop2space) ? $afterstring.substr($content,$trunc_at) : $afterstring.$content;
				} else {
					$content = $GLOBALS['TSFE']->csConvObj->substr($GLOBALS['TSFE']->renderCharset,$content,0,$chars);
					$trunc_at = strrpos($content, ' ');
					$content = ($trunc_at&&$crop2space) ? substr($content, 0, $trunc_at).$afterstring : $content.$afterstring;
				}
			}
		}
		return $content;
	}

	/**
	 * Implements the stdWrap property "cropHTML" which is a modified "substr" function allowing to limit a string length
	 * to a certain number of chars (from either start or end of string) and having a pre/postfix applied if the string
	 * really was cropped.
	 *
	 * Compared to stdWrap.crop it respects HTML tags and entities.
	 *
	 * @param	string		The string to perform the operation on
	 * @param	string		The parameters splitted by "|": First parameter is the max number of chars of the string. Negative value means cropping from end of string. Second parameter is the pre/postfix string to apply if cropping occurs. Third parameter is a boolean value. If set then crop will be applied at nearest space.
	 * @return	string		The processed input value.
	 * @access private
	 * @see stdWrap()
	 */
	function cropHTML($content, $options) {
		$options = explode('|', $options);
		$chars = intval($options[0]);
		$absChars = abs($chars);
		$replacementForEllipsis = trim($options[1]);
		$crop2space = trim($options[2]) === '1' ? TRUE : FALSE;

		// Split $content into an array (even items in the array are outside the tags, odd numbers are tag-blocks).
		$tags= 'a|b|blockquote|body|div|em|font|form|h1|h2|h3|h4|h5|h6|i|li|map|ol|option|p|pre|sub|sup|select|span|strong|table|thead|tbody|tfoot|td|textarea|tr|u|ul|br|hr|img|input|area|link';
		// TODO We should not crop inside <script> tags.
		$tagsRegEx = "
			(
				(?:
					<!--.*?-->					# a comment
				)
				|
				</?(?:". $tags . ")+			# opening tag ('<tag') or closing tag ('</tag')
				(?:
					(?:
						\s+\w+					# EITHER spaces, followed by word characters (attribute names)
						(?:
							\s*=?\s*			# equals
							(?>
								\".*?\"			# attribute values in double-quotes
								|
								'.*?'			# attribute values in single-quotes
								|
								[^'\">\s]+		# plain attribute values
							)
						)?
					)+\s*
					|							# OR only spaces
					\s*
				)
				/?>								# closing the tag with '>' or '/>'
			)";
		$splittedContent = preg_split('%' . $tagsRegEx . '%xs', $content, -1, PREG_SPLIT_DELIM_CAPTURE);

		// Reverse array if we are cropping from right.
		if ($chars < 0) {
			$splittedContent = array_reverse($splittedContent);
		}

		// Crop the text (chars of tag-blocks are not counted).
		$strLen = 0;
		$croppedOffset = NULL; // This is the offset of the content item which was cropped.
		$countSplittedContent = count($splittedContent);
		for ($offset = 0; $offset < $countSplittedContent; $offset++) {
			if ($offset%2 === 0) {
				$tempContent = $GLOBALS['TSFE']->csConvObj->utf8_encode($splittedContent[$offset], $GLOBALS['TSFE']->renderCharset);
				$thisStrLen = $GLOBALS['TSFE']->csConvObj->strlen('utf-8', html_entity_decode($tempContent, ENT_COMPAT, 'UTF-8'));
				if (($strLen + $thisStrLen > $absChars)) {
					$croppedOffset = $offset;
					$cropPosition = $absChars - $strLen;
						// The snippet "&[^&\s;]{2,8};" in the RegEx below represents entities.
					$patternMatchEntityAsSingleChar = '(&[^&\s;]{2,8};|.)';
					if ($crop2space) {
						$cropRegEx = $chars < 0 ?
							'#(?<=\s)' . $patternMatchEntityAsSingleChar . '{0,' . $cropPosition . '}$#ui' :
							'#^' . $patternMatchEntityAsSingleChar . '{0,' . $cropPosition . '}(?=\s)#ui';
					} else {
						$cropRegEx = $chars < 0 ?
							'#' . $patternMatchEntityAsSingleChar . '{0,' . $cropPosition . '}$#ui' :
							'#^' . $patternMatchEntityAsSingleChar . '{0,' . $cropPosition . '}#ui';
					}
					if (preg_match($cropRegEx, $tempContent, $croppedMatch)) {
						$tempContent = $croppedMatch[0];
					}
					$splittedContent[$offset] = $GLOBALS['TSFE']->csConvObj->utf8_decode($tempContent, $GLOBALS['TSFE']->renderCharset);
					break;
				} else {
					$strLen += $thisStrLen;
				}
			}
		}

		// Close cropped tags.
		$closingTags = array();
		if($croppedOffset !== NULL) {
			$tagName = '';
			$openingTagRegEx = '#^<(\w+)(?:\s|>)#';
			$closingTagRegEx = '#^</(\w+)(?:\s|>)#';
			for ($offset = $croppedOffset - 1; $offset >= 0; $offset = $offset - 2) {
				if (substr($splittedContent[$offset], -2) === '/>') {
					// Ignore empty element tags (e.g. <br />).
					continue;
				}
				preg_match($chars < 0 ? $closingTagRegEx : $openingTagRegEx, $splittedContent[$offset], $matches);
				$tagName = isset($matches[1]) ? $matches[1] : NULL;
				if ($tagName !== NULL) {
					// Seek for the closing (or opening) tag.
					$seekingTagName = '';
					$countSplittedContent = count($splittedContent);
					for ($seekingOffset = $offset + 2; $seekingOffset < $countSplittedContent; $seekingOffset = $seekingOffset + 2) {
						preg_match($chars < 0 ? $openingTagRegEx : $closingTagRegEx, $splittedContent[$seekingOffset], $matches);
						$seekingTagName = isset($matches[1]) ? $matches[1] : NULL;
						if ($tagName === $seekingTagName) { // We found a matching tag.
							// Add closing tag only if it occurs after the cropped content item.
							if ($seekingOffset > $croppedOffset) {
								$closingTags[] = $splittedContent[$seekingOffset];
							}
							break;
						}
					}
				}
			}
			// Drop the cropped items of the content array. The $closingTags will be added later on again.
			array_splice($splittedContent, $croppedOffset + 1);
		}
		$splittedContent = array_merge($splittedContent, array($croppedOffset !== NULL ? $replacementForEllipsis : ''), $closingTags);

		// Reverse array once again if we are cropping from the end.
		if ($chars < 0) {
			$splittedContent = array_reverse($splittedContent);
		}

		return implode('', $splittedContent);
	}

	/**
	 * Function for removing malicious HTML code when you want to provide some HTML code user-editable.
	 * The purpose is to avoid XSS attacks and the code will be continously modified to remove such code.
	 * For a complete reference with javascript-on-events, see http://www.wdvl.com/Authoring/JavaScript/Events/events_target.html
	 *
	 * @param	string		Input string to be cleaned.
	 * @param	array		TypoScript configuration.
	 * @return	string		Return string
	 * @author	Thomas Bley (all from moregroupware cvs code / readmessage.inc.php, published under gpl by Thomas)
	 * @author	Kasper Skaarhoj
	 */
	function removeBadHTML($text, $conf)	{

			// Copyright 2002-2003 Thomas Bley
		$text = preg_replace(
			array(
				"'<script[^>]*?>.*?</script[^>]*?>'si",
				"'<applet[^>]*?>.*?</applet[^>]*?>'si",
				"'<object[^>]*?>.*?</object[^>]*?>'si",
				"'<iframe[^>]*?>.*?</iframe[^>]*?>'si",
				"'<frameset[^>]*?>.*?</frameset[^>]*?>'si",
				"'<style[^>]*?>.*?</style[^>]*?>'si",
				"'<marquee[^>]*?>.*?</marquee[^>]*?>'si",
				"'<script[^>]*?>'si",
				"'<meta[^>]*?>'si",
				"'<base[^>]*?>'si",
				"'<applet[^>]*?>'si",
				"'<object[^>]*?>'si",
				"'<link[^>]*?>'si",
				"'<iframe[^>]*?>'si",
				"'<frame[^>]*?>'si",
				"'<frameset[^>]*?>'si",
				"'<input[^>]*?>'si",
				"'<form[^>]*?>'si",
				"'<embed[^>]*?>'si",
				"'background-image:url'si",
				"'<\w+.*?(onabort|onbeforeunload|onblur|onchange|onclick|ondblclick|ondragdrop|onerror|onfilterchange|onfocus|onhelp|onkeydown|onkeypress|onkeyup|onload|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|onmove|onreadystatechange|onreset|onresize|onscroll|onselect|onselectstart|onsubmit|onunload).*?>'si",
			), '', $text);

			$text = preg_replace('/<a[^>]*href[[:space:]]*=[[:space:]]*["\']?[[:space:]]*javascript[^>]*/i','',$text);

			// Return clean content
		return $text;
	}

	/**
	 * Implements the stdWrap property "textStyle"; This generates a <font>-tag (and a <div>-tag for align-attributes) which is wrapped around the input value.
	 *
	 * @param	string		The input value
	 * @param	array		TypoScript properties for the "TypoScript function" '->textStyle'
	 * @return	string		The processed output value
	 * @access private
	 * @see stdWrap()
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=322&cHash=a14b745a18
	 */
	function textStyle($theValue, $conf) {
		$conf['face.'][1] = 'Times New Roman';
		$conf['face.'][2] = 'Verdana,Arial,Helvetica,Sans serif';
		$conf['face.'][3] = 'Arial,Helvetica,Sans serif';

		$conf['size.'][1] = 1;
		$conf['size.'][2] = 2;
		$conf['size.'][3] = 3;
		$conf['size.'][4] = 4;
		$conf['size.'][5] = 5;
		$conf['size.'][10] = '+1';
		$conf['size.'][11] = '-1';

		$conf['color.'][240] = 'black';
		$conf['color.'][241] = 'white';
		$conf['color.'][242] = '#333333';
		$conf['color.'][243] = 'gray';
		$conf['color.'][244] = 'silver';
		$conf['color.'][245] = 'red';
		$conf['color.'][246] = 'navy';
		$conf['color.'][247] = 'yellow';
		$conf['color.'][248] = 'green';
		$conf['color.'][249] = 'olive';
		$conf['color.'][250] = 'maroon';

		$face = $this->data[$conf['face.']['field']];
		$size = $this->data[$conf['size.']['field']];
		$color = $this->data[$conf['color.']['field']];
		$align = $this->data[$conf['align.']['field']];
		$properties = $this->data[$conf['properties.']['field']];
		if (!$properties)	{
			$properties=$this->stdWrap($conf['properties.']['default'],$conf['properties.']['default.']);
		}

			// properties
		if (($properties&8))	{$theValue=$this->HTMLcaseshift($theValue, 'upper');}
		if (($properties&1))	{$theValue='<strong>'.$theValue.'</strong>';}
		if (($properties&2))	{$theValue='<i>'.$theValue.'</i>';}
		if (($properties&4))	{$theValue='<u>'.$theValue.'</u>';}

			// Fonttag
		$theFace = $conf['face.'][$face] ? $conf['face.'][$face] : $this->stdWrap($conf['face.']['default'],$conf['face.']['default.']);
		$theSize = $conf['size.'][$size] ? $conf['size.'][$size] : $this->stdWrap($conf['size.']['default'],$conf['size.']['default.']);
		$theColor = $conf['color.'][$color] ? $conf['color.'][$color] : $this->stdWrap($conf['color.']['default'],$conf['color.']['default.']);

		if ($conf['altWrap'])	{
			$theValue=$this->wrap($theValue, $conf['altWrap']);
		} elseif ($theFace || $theSize || $theColor)	{
			$fontWrap = '<font'.($theFace?' face="'.$theFace.'"':'').($theSize?' size="'.$theSize.'"':'').($theColor?' color="'.$theColor.'"':'').'>|</font>';
			$theValue=$this->wrap($theValue, $fontWrap);
		}
			// align
		if ($align)	{$theValue=$this->wrap($theValue, '<div style="text-align:'.$align.';">|</div>');}
			// return
		return $theValue;
	}

	/**
	 * Implements the stdWrap property "tableStyle"; Basically this generates a <table>-tag with properties which is wrapped around the input value.
	 *
	 * @param	string		The input value
	 * @param	array		TypoScript properties for the "TypoScript function" '->textStyle'
	 * @return	string		The processed output value
	 * @access private
	 * @see stdWrap()
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=324&cHash=34410ebff3
	 */
	function tableStyle($theValue, $conf) {
		$conf['color.'][240] = 'black';
		$conf['color.'][241] = 'white';
		$conf['color.'][242] = '#333333';
		$conf['color.'][243] = 'gray';
		$conf['color.'][244] = 'silver';

		$align = $this->stdWrap($conf['align'],$conf['align.']);
		$border = intval($this->stdWrap($conf['border'],$conf['border.']));
		$cellspacing = intval($this->stdWrap($conf['cellspacing'],$conf['cellspacing.']));
		$cellpadding = intval($this->stdWrap($conf['cellpadding'],$conf['cellpadding.']));

		$color = $this->data[$conf['color.']['field']];
		$theColor = $conf['color.'][$color] ? $conf['color.'][$color] : $conf['color.']['default'];
			// Assembling the table tag
		$tableTagArray = Array('<table');
		$tableTagArray[]='border="'.$border.'"';
		$tableTagArray[]='cellspacing="'.$cellspacing.'"';
		$tableTagArray[]='cellpadding="'.$cellpadding.'"';
		if ($align)	{$tableTagArray[]='align="'.$align.'"';}
		if ($theColor)	{$tableTagArray[]='bgcolor="'.$theColor.'"';}

		if ($conf['params'])	{
			$tableTagArray[] = $conf['params'];
		}

		$tableWrap = implode(' ',$tableTagArray).'> | </table>';
		$theValue=$this->wrap($theValue, $tableWrap);
			// return
		return $theValue;
	}

	/**
	 * Implements the TypoScript function "addParams"
	 *
	 * @param	string		The string with the HTML tag.
	 * @param	array		The TypoScript configuration properties
	 * @return	string		The modified string
	 * @todo	Make it XHTML compatible. Will not present "/>" endings of tags right now. Further getting the tagname might fail if it is not separated by a normal space from the attributes.
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=325&cHash=ae4272e694
	 */
	function addParams($content,$conf) {
		$lowerCaseAttributes = TRUE;	// For XHTML compliance.

		if (!is_array($conf))	{ return $content; }

		$key = 1;
		$parts = explode('<',$content);
		if (intval($conf['_offset']))	$key = intval($conf['_offset'])<0 ? count($parts)+intval($conf['_offset']) : intval($conf['_offset']);
		$subparts=explode('>',$parts[$key]);
		if (trim($subparts[0]))	{
				// Get attributes and name
			$attribs = t3lib_div::get_tag_attributes('<'.$subparts[0].'>');
			list($tagName) = explode(' ',$subparts[0],2);
				// adds/overrides attributes
			foreach ($conf as $pkey => $val)	{
				if (substr($pkey,-1)!='.' && substr($pkey,0,1)!='_')	{
					$tmpVal=$this->stdWrap($conf[$pkey],$conf[$pkey.'.']);
					if ($lowerCaseAttributes)	{ $pkey = strtolower($pkey); }
					if (strcmp($tmpVal,''))	{$attribs[$pkey]=$tmpVal;}
				}
			}

				// Re-assembles the tag and content
			$subparts[0] = trim($tagName.' '.t3lib_div::implodeAttributes($attribs));
			$parts[$key] = implode('>',$subparts);
			$content = implode('<',$parts);
		}
		return $content;
	}

	/**
	 * Creates a list of links to files.
	 * Implements the stdWrap property "filelink"
	 *
	 * @param	string		The filename to link to, possibly prefixed with $conf[path]
	 * @param	array		TypoScript parameters for the TypoScript function ->filelink
	 * @return	string		The link to the file possibly with icons, thumbnails, size in bytes shown etc.
	 * @access private
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=326&cHash=5618043c18
	 * @see stdWrap()
	 */
	function filelink($theValue, $conf)	{
		$conf['path'] = $this->stdWrap($conf['path'], $conf['path.']);
		$theFile = trim($conf['path']) . $theValue;
		if (@is_file($theFile))	{
			$theFileEnc = str_replace('%2F', '/', rawurlencode($theFile));

			// the jumpURL feature will be taken care of by typoLink, only "jumpurl.secure = 1" is applyable needed for special link creation
			if ($conf['jumpurl.']['secure']) {
				$alternativeJumpUrlParameter = $this->stdWrap($conf['jumpurl.']['parameter'], $conf['jumpurl.']['parameter.']);
				$typoLinkConf = array(
					'parameter'  => ($alternativeJumpUrlParameter ? $alternativeJumpUrlParameter : ($GLOBALS['TSFE']->id . ',' . $GLOBALS['TSFE']->type)),
					'fileTarget' => $conf['target'],
					'ATagParams' => $this->getATagParams($conf),
					'additionalParams' => '&jumpurl=' . rawurlencode($theFileEnc) . $this->locDataJU($theFileEnc, $conf['jumpurl.']['secure.']) . $GLOBALS['TSFE']->getMethodUrlIdToken
				);
			} else {
				$typoLinkConf = array(
					'parameter'  => $theFileEnc,
					'fileTarget' => $conf['target'],
					'ATagParams' => $this->getATagParams($conf)
				);
			}

				// if the global jumpURL feature is activated, but is disabled for this
				// filelink, the global parameter needs to be disabled as well for this link creation
			$globalJumpUrlEnabled = $GLOBALS['TSFE']->config['config']['jumpurl_enable'];
			if ($globalJumpUrlEnabled && isset($conf['jumpurl']) && $conf['jumpurl'] == 0) {
				$GLOBALS['TSFE']->config['config']['jumpurl_enable'] = 0;
				// if the global jumpURL feature is deactivated, but is wanted for this link, then activate it for now
			} else if (!$globalJumpUrlEnabled && $conf['jumpurl']) {
				$GLOBALS['TSFE']->config['config']['jumpurl_enable'] = 1;
			}
			$theLinkWrap = $this->typoLink('|', $typoLinkConf);

			// now the original value is set again
			$GLOBALS['TSFE']->config['config']['jumpurl_enable'] = $globalJumpUrlEnabled;

			$theSize = filesize($theFile);
			$fI = t3lib_div::split_fileref($theFile);
			if ($conf['icon'])	{
				$iconP = t3lib_extMgm::siteRelPath('cms').'tslib/media/fileicons/';
				$icon = @is_file($iconP.$fI['fileext'].'.gif') ? $iconP.$fI['fileext'].'.gif' : $iconP.'default.gif';
					// Checking for images: If image, then return link to thumbnail.
				$IEList = $this->stdWrap($conf['icon_image_ext_list'],$conf['icon_image_ext_list.']);
				$image_ext_list = str_replace(' ','',strtolower($IEList));
				if ($fI['fileext'] && t3lib_div::inList($image_ext_list, $fI['fileext']))	{
					if ($conf['iconCObject'])	{
						$icon = $this->cObjGetSingle($conf['iconCObject'],$conf['iconCObject.'],'iconCObject');
					} else {
						if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'])	{
							$thumbSize = '';
							if ($conf['icon_thumbSize'] || $conf['icon_thumbSize.'])	{ $thumbSize = '&size='.$this->stdWrap($conf['icon_thumbSize'], $conf['icon_thumbSize.']); }
							$check = basename($theFile).':'.filemtime($theFile).':'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
							$md5sum = '&md5sum='.t3lib_div::shortMD5($check);
							$icon = 't3lib/thumbs.php?dummy='.$GLOBALS['EXEC_TIME'].'&file='.rawurlencode('../'.$theFile).$thumbSize.$md5sum;
						} else {
							$icon = t3lib_extMgm::siteRelPath('cms').'tslib/media/miscicons/notfound_thumb.gif';
						}
						$icon = '<img src="'.htmlspecialchars($GLOBALS['TSFE']->absRefPrefix.$icon).'"'.$this->getBorderAttr(' border="0"').''.$this->getAltParam($conf).' />';
					}
				} else {
					$icon = '<img src="'.htmlspecialchars($GLOBALS['TSFE']->absRefPrefix.$icon).'" width="18" height="16"'.$this->getBorderAttr(' border="0"').''.$this->getAltParam($conf).' />';
				}
				if ($conf['icon_link']) {$icon = $this->wrap($icon, $theLinkWrap);}
				$icon = $this->stdWrap($icon,$conf['icon.']);
			}
			if ($conf['size'])	{
				$size = $this->stdWrap($theSize,$conf['size.']);
			}

				// Wrapping file label
			if ($conf['removePrependedNumbers']) $theValue=preg_replace('/_[0-9][0-9](\.[[:alnum:]]*)$/','\1',$theValue);
			$theValue = $this->stdWrap($theValue,$conf['labelStdWrap.']);

				// Wrapping file
			if ($conf['ATagBeforeWrap'])	{
				$theValue = $this->wrap($this->wrap($theValue, $conf['wrap']), $theLinkWrap);
			} else {
				$theValue = $this->wrap($this->wrap($theValue, $theLinkWrap), $conf['wrap']);
			}
			$file = $this->stdWrap($theValue,$conf['file.']);
				// output
			return $this->stdWrap($icon.$file.$size, $conf['stdWrap.']);
		}
	}

	/**
	 * Returns a URL parameter string setting parameters for secure downloads by "jumpurl".
	 * Helper function for filelink()
	 *
	 * @param	string		The URL to jump to, basically the filepath
	 * @param	array		TypoScript properties for the "jumpurl.secure" property of "filelink"
	 * @return	string		URL parameters like "&juSecure=1....."
	 * @access private
	 * @see filelink()
	 */
	function locDataJU($jumpUrl,$conf)	{
		$fI = pathinfo($jumpUrl);
		$mimetype='';
		if ($fI['extension'])	{
			$mimeTypes = t3lib_div::trimExplode(',',$conf['mimeTypes'],1);
			foreach ($mimeTypes as $v) {
				$parts = explode('=',$v,2);
				if (strtolower($fI['extension']) == strtolower(trim($parts[0])))	{
					$mimetype = '&mimeType='.rawurlencode(trim($parts[1]));
				}
			}
		}
		$locationData = $GLOBALS['TSFE']->id.':'.$this->currentRecord;
		$rec='&locationData='.rawurlencode($locationData);
		$hArr = array(
			$jumpUrl,
			$locationData,
			$GLOBALS['TSFE']->TYPO3_CONF_VARS['SYS']['encryptionKey']
		);
		$juHash='&juHash='.t3lib_div::shortMD5(serialize($hArr));
		return '&juSecure=1'.$mimetype.$rec.$juHash;
	}

	/**
	 * Performs basic mathematical evaluation of the input string. Does NOT take parathesis and operator precedence into account! (for that, see t3lib_div::calcPriority())
	 *
	 * @param	string		The string to evaluate. Example: "3+4*10/5" will generate "35". Only integer numbers can be used.
	 * @return	integer		The result (might be a float if you did a division of the numbers).
	 * @see t3lib_div::calcPriority()
	 */
	function calc($val)	{
		$parts= t3lib_div::splitCalc($val,'+-*/');
		$value=0;
		foreach ($parts as $part) {
			$theVal = $part[1];
			$sign =  $part[0];
			if ((string)intval($theVal)==(string)$theVal)	{
				$theVal = intval($theVal);
			} else {
				$theVal =0;
			}
			if ($sign=='-')	{$value-=$theVal;}
			if ($sign=='+')	{$value+=$theVal;}
			if ($sign=='/')	{if (intval($theVal)) $value/=intval($theVal);}
			if ($sign=='*')	{$value*=$theVal;}
		}
		return $value;
	}

	/**
	 * This explodes a comma-list into an array where the values are parsed through tslib_cObj::calc() and intval() (so you are sure to have integers in the output array)
	 * Used to split and calculate min and max values for GMENUs.
	 *
	 * @param	string		Delimited to explode by
	 * @param	string		The string with parts in (where each part is evaluated by ->calc())
	 * @return	array		And array with evaluated values.
	 * @see calc(), tslib_gmenu::makeGifs()
	 */
	function calcIntExplode($delim, $string)	{
		$temp = explode($delim,$string);
		foreach ($temp as $key => $val) {
			$temp[$key]=intval(tslib_cObj::calc($val));
		}
		return $temp;
	}

	/**
	 * Implements the "split" property of stdWrap; Splits a string based on a token (given in TypoScript properties), sets the "current" value to each part and then renders a content object pointer to by a number.
	 * In classic TypoScript (like 'content (default)'/'styles.content (default)') this is used to render tables, splitting rows and cells by tokens and putting them together again wrapped in <td> tags etc.
	 * Implements the "optionSplit" processing of the TypoScript options for each splitted value to parse.
	 *
	 * @param	string		The string value to explode by $conf[token] and process each part
	 * @param	array		TypoScript properties for "split"
	 * @return	string		Compiled result
	 * @access private
	 * @see stdWrap(), t3lib_menu::procesItemStates()
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=319&cHash=1871864c8f
	 */
	function splitObj($value, $conf)	{
		$conf['token']=$this->stdWrap($conf['token'],$conf['token.']);
		if (!$conf['token'])	{
			return $value;
		}
		$conf['max']=intval($this->stdWrap($conf['max'],$conf['max.']));
		$conf['min']=intval($this->stdWrap($conf['min'],$conf['min.']));

		$valArr=explode($conf['token'],$value);

		if (count($valArr) && (t3lib_div::testInt($conf['returnKey']) || $conf['returnKey.']))	{
			$key = intval($this->stdWrap($conf['returnKey'],$conf['returnKey.']));
			$content = isset($valArr[$key]) ? $valArr[$key] : '';
		} else {
				// calculate splitCount
			$splitCount = count($valArr);
			if ($conf['max'] && $splitCount>$conf['max'])	{
				$splitCount=$conf['max'];
			}
			if ($conf['min'] && $splitCount<$conf['min'])	{
				$splitCount=$conf['min'];
			}

			if ($conf['wrap'] || $conf['cObjNum'])	{
				$splitArr=array();
				$splitArr['wrap']=$conf['wrap'];
				$splitArr['cObjNum']=$conf['cObjNum'];
				$splitArr = $GLOBALS['TSFE']->tmpl->splitConfArray($splitArr,$splitCount);
			}

			$content='';
			for($a=0;$a<$splitCount;$a++)	{
				$GLOBALS['TSFE']->register['SPLIT_COUNT']=$a;
				$value = ''.$valArr[$a];
				$this->data[$this->currentValKey] = $value;
				if ($splitArr[$a]['cObjNum'])	{
					$objName=intval($splitArr[$a]['cObjNum']);
					$value = $this->stdWrap($this->cObjGet($conf[$objName.'.'],$objName.'.'),$conf[$objName.'.']);
				}
				if ($splitArr[$a]['wrap'])	{
					$value=$this->wrap($value,$splitArr[$a]['wrap']);
				}
				$content.=$value;
			}
		}
		return $content;
	}

	/**
	 * Implements the stdWrap property, "parseFunc".
	 * This is a function with a lot of interesting uses. In classic TypoScript this is used to process text from the bodytext field; This included highlighting of search words, changing http:// and mailto: prefixed strings into links, parsing <typolist>, <typohead> and <typocode> tags etc.
	 * It is still a very important function for processing of bodytext which is normally stored in the database in a format which is not fully ready to be outputted. This situation has not become better by having a RTE around...
	 *
	 * This function is actually just splitting the input content according to the configuration of "external blocks". This means that before the input string is actually "parsed" it will be splitted into the parts configured to BE parsed (while other parts/blocks should NOT be parsed). Therefore the actual processing of the parseFunc properties goes on in ->_parseFunc()
	 *
	 * @param	string		The value to process.
	 * @param	array		TypoScript configuration for parseFunc
	 * @param	string		Reference to get configuration from. Eg. "< lib.parseFunc" which means that the configuration of the object path "lib.parseFunc" will be retrieved and MERGED with what is in $conf!
	 * @return	string		The processed value
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=327&cHash=33331f0396
	 * @see _parseFunc()
	 */
	function parseFunc($theValue, $conf, $ref='') {

			// Fetch / merge reference, if any
		if ($ref)	{
			$temp_conf = array(
								'parseFunc' => $ref,
								'parseFunc.' => $conf
							);
			$temp_conf = $this->mergeTSRef($temp_conf, 'parseFunc');
			$conf = $temp_conf['parseFunc.'];
		}

			// Process:
		if (strcmp($conf['externalBlocks'],''))	{
			$tags = strtolower(implode(',',t3lib_div::trimExplode(',',$conf['externalBlocks'])));
			$htmlParser = t3lib_div::makeInstance('t3lib_parsehtml');
			$parts = $htmlParser->splitIntoBlock($tags,$theValue);

			foreach ($parts as $k => $v) {
				if ($k%2)	{	// font:
					$tagName=strtolower($htmlParser->getFirstTagName($v));
					$cfg=$conf['externalBlocks.'][$tagName.'.'];
					if ($cfg['stripNLprev'] || $cfg['stripNL'])	{
						$parts[$k-1]=preg_replace('/'.CR.'?'.LF.'[ ]*$/', '', $parts[$k-1]);
					}
					if ($cfg['stripNLnext'] || $cfg['stripNL'])	{
						$parts[$k+1]=preg_replace('/^[ ]*'.CR.'?'.LF.'/', '', $parts[$k+1]);
					}
				}
			}

			foreach ($parts as $k => $v) {
				if ($k%2)	{
					$tag=$htmlParser->getFirstTag($v);
					$tagName=strtolower($htmlParser->getFirstTagName($v));
					$cfg=$conf['externalBlocks.'][$tagName.'.'];
					if ($cfg['callRecursive'])	{
						$parts[$k]=$this->parseFunc($htmlParser->removeFirstAndLastTag($v), $conf);
						if (!$cfg['callRecursive.']['dontWrapSelf'])	{
							if ($cfg['callRecursive.']['alternativeWrap'])	{
								$parts[$k] = $this->wrap($parts[$k], $cfg['callRecursive.']['alternativeWrap']);
							} else {
								if (is_array($cfg['callRecursive.']['tagStdWrap.']))	{
									$tag = $this->stdWrap($tag,$cfg['callRecursive.']['tagStdWrap.']);
								}
								$parts[$k]=$tag.$parts[$k].'</'.$tagName.'>';
							}
						}
					} elseif($cfg['HTMLtableCells']) {
						$rowParts = $htmlParser->splitIntoBlock('tr',$parts[$k]);
						foreach ($rowParts as $kk => $vv) {
							if ($kk%2)	{
								$colParts = $htmlParser->splitIntoBlock('td,th',$vv);
								$cc=0;
								foreach ($colParts as $kkk => $vvv) {
									if ($kkk%2)	{
										$cc++;
										$tag=$htmlParser->getFirstTag($vvv);
										$tagName=strtolower($htmlParser->getFirstTagName($vvv));
										$colParts[$kkk] = $htmlParser->removeFirstAndLastTag($vvv);

										if ($cfg['HTMLtableCells.'][$cc.'.']['callRecursive'] || (!isset($cfg['HTMLtableCells.'][$cc.'.']['callRecursive']) && $cfg['HTMLtableCells.']['default.']['callRecursive']))	{
											if ($cfg['HTMLtableCells.']['addChr10BetweenParagraphs'])	$colParts[$kkk]=str_replace('</p><p>','</p>'.LF.'<p>',$colParts[$kkk]);
											$colParts[$kkk] = $this->parseFunc($colParts[$kkk], $conf);
										}

										$tagStdWrap = is_array($cfg['HTMLtableCells.'][$cc.'.']['tagStdWrap.'])?$cfg['HTMLtableCells.'][$cc.'.']['tagStdWrap.']:$cfg['HTMLtableCells.']['default.']['tagStdWrap.'];
										if (is_array($tagStdWrap))	{
											$tag = $this->stdWrap($tag,$tagStdWrap);
										}

										$stdWrap = is_array($cfg['HTMLtableCells.'][$cc.'.']['stdWrap.'])?$cfg['HTMLtableCells.'][$cc.'.']['stdWrap.']:$cfg['HTMLtableCells.']['default.']['stdWrap.'];
										if (is_array($stdWrap))	{
											$colParts[$kkk] = $this->stdWrap($colParts[$kkk],$stdWrap);
										}

										$colParts[$kkk]=$tag.$colParts[$kkk].'</'.$tagName.'>';
									}
								}
								$rowParts[$kk] = implode('',$colParts);
							}
						}
						$parts[$k] = implode('',$rowParts);
					}

					if (is_array($cfg['stdWrap.']))	{
						$parts[$k] = $this->stdWrap($parts[$k],$cfg['stdWrap.']);
					}
				} else {
					$parts[$k]=$this->_parseFunc($parts[$k], $conf);
				}
			}

			return implode('',$parts);
		} else return $this->_parseFunc($theValue, $conf);
	}

	/**
	 * Helper function for parseFunc()
	 *
	 * @param	string		The value to process.
	 * @param	array		TypoScript configuration for parseFunc
	 * @return	string		The processed value
	 * @access private
	 * @see parseFunc()
	 */
	function _parseFunc ($theValue, $conf) {
		if (!$this->checkIf($conf['if.']))	{
			return $theValue;
		}
		$inside=0;	// Indicates that the data is from within a tag.
		$pointer=0;	// Pointer to the total string position
		$currentTag='';	// Loaded with the current typo-tag if any.
		$stripNL=0;
		$contentAccum=array();
		$contentAccumP=0;

		$allowTags=strtolower(str_replace(' ','',$conf['allowTags']));
		$denyTags=strtolower(str_replace(' ','',$conf['denyTags']));

		$totalLen = strlen($theValue);
		do	{
			if (!$inside)	{
				if (!is_array($currentTag))	{			// These operations should only be performed on code outside the typotags...
						// data: this checks that we enter tags ONLY if the first char in the tag is alphanumeric OR '/'
					$len_p=0;
					$c=100;
					do 	{
						$len = strcspn(substr($theValue,$pointer+$len_p),'<');
						$len_p+=$len+1;
						$endChar = ord(strtolower(substr($theValue,$pointer+$len_p,1)));
						$c--;
					} while ($c>0 && $endChar && ($endChar<97 || $endChar>122) && $endChar!=47);
					$len = $len_p-1;
				} else {
						// If we're inside a currentTag, just take it to the end of that tag!
					$tempContent = strtolower(substr($theValue,$pointer));
					$len = strpos ($tempContent, '</'.$currentTag[0]);
 					if (is_string ($len) && !$len) {
						$len = strlen($tempContent);
					}
				}

				$data = substr($theValue,$pointer,$len);	// $data is the content until the next <tag-start or end is detected. In case of a currentTag set, this would mean all data between the start- and end-tags
				if ($data!='')	{
					if ($stripNL)	{		// If the previous tag was set to strip NewLines in the beginning of the next data-chunk.
						$data = preg_replace('/^[ ]*'.CR.'?'.LF.'/', '', $data);
					}

					if (!is_array($currentTag))	{			// These operations should only be performed on code outside the tags...
							// Constants
						$tmpConstants = $GLOBALS['TSFE']->tmpl->setup['constants.'];
						if ($conf['constants'] && is_array($tmpConstants)) {
							foreach ($tmpConstants as $key => $val) {
								if (is_string($val))	{
									$data = str_replace('###'.$key.'###', $val, $data);
								}
							}
						}
							// Short
						if (is_array($conf['short.']))	{
							$shortWords = $conf['short.'];
							krsort($shortWords);
							foreach ($shortWords as $key => $val) {
								if (is_string($val))	{
									$data = str_replace($key, $val, $data);
								}
							}
						}

							// stdWrap
						if (is_array($conf['plainTextStdWrap.']))	{$data = $this->stdWrap($data,$conf['plainTextStdWrap.']);}
							// userFunc
						if ($conf['userFunc'])	{$data = $this->callUserFunction($conf['userFunc'], $conf['userFunc.'], $data);}

							// Makelinks: (Before search-words as we need the links to be generated when searchwords go on...!)
						if ($conf['makelinks'])	{
							$data = $this->http_makelinks($data,$conf['makelinks.']['http.']);
							$data = $this->mailto_makelinks($data,$conf['makelinks.']['mailto.']);
						}

							// Search Words:
						if ($GLOBALS['TSFE']->no_cache && $conf['sword'] && is_array($GLOBALS['TSFE']->sWordList) && $GLOBALS['TSFE']->sWordRegEx)	{
							$newstring = '';
							do {
								$pieces = preg_split('/' . $GLOBALS['TSFE']->sWordRegEx . '/', $data, 2);
								$newstring.=$pieces[0];
								$match_len = strlen($data)-(strlen($pieces[0])+strlen($pieces[1]));
								if (strstr($pieces[0],'<') || strstr($pieces[0],'>'))	{
									$inTag = strrpos($pieces[0],'<') > strrpos($pieces[0],'>');		// Returns true, if a '<' is closer to the string-end than '>'. This is the case if we're INSIDE a tag (that could have been made by makelinks...) and we must secure, that the inside of a tag is not marked up.
								}
									// The searchword:
								$match = substr($data,strlen($pieces[0]),$match_len);

								if (trim($match) && strlen($match)>1 && !$inTag)	{
									$match = $this->wrap($match, $conf['sword']);
								}
									// Concatenate the Search Word again.
								$newstring.= $match;
								$data=$pieces[1];
							} while ($pieces[1]);
							$data = $newstring;
						}
					}
					$contentAccum[$contentAccumP].=$data;
				}
				$inside = 1;
			} else {
					// tags
				$len = strcspn(substr($theValue,$pointer),'>')+1;
				$data = substr($theValue,$pointer,$len);

				$tag = explode(' ',trim(substr($data,1,-1)),2);
				$tag[0]=strtolower($tag[0]);
				if (substr($tag[0],0,1)=='/')	{
					$tag[0]=substr($tag[0],1);
					$tag['out']=1;
				}
				if($conf['tags.'][$tag[0]])	{
					$treated=0;
					$stripNL = 0;
						// in-tag
					if (!$currentTag && !$tag['out'])	{
						$currentTag=$tag;		// $currentTag (array!) is the tag we are currently processing
 						$contentAccumP++;
 						$treated=1;
							// in-out-tag: img and other empty tags
						if ($tag[0]=='img' || substr($tag[1],-3,2)==' /')	{
							$tag['out']=1;
						}
 					}
						// out-tag
					if ($currentTag[0]==$tag[0] && $tag['out'])	{
						$theName = $conf['tags.'][$tag[0]];
						$theConf = $conf['tags.'][$tag[0].'.'];
						$stripNL = ($theConf['stripNL'] ? 1 : 0);	// This flag indicates, that NL- (13-10-chars) should be stripped first and last.
						$breakOut = ($theConf['breakoutTypoTagContent'] ? 1 : 0);	// This flag indicates, that this TypoTag section should NOT be included in the nonTypoTag content.

						$this->parameters=Array();
						if ($currentTag[1])	{
							$params=t3lib_div::get_tag_attributes($currentTag[1]);
							if (is_array($params))	{
								foreach ($params as $option => $val) {
									$this->parameters[strtolower($option)]=$val;
								}
							}
						}
						$this->parameters['allParams']=trim($currentTag[1]);
						if ($stripNL)	{	// Removes NL in the beginning and end of the tag-content AND at the end of the currentTagBuffer. $stripNL depends on the configuration of the current tag
							$contentAccum[$contentAccumP-1] = preg_replace('/'.CR.'?'.LF.'[ ]*$/', '', $contentAccum[$contentAccumP-1]);
							$contentAccum[$contentAccumP] = preg_replace('/^[ ]*'.CR.'?'.LF.'/', '', $contentAccum[$contentAccumP]);
							$contentAccum[$contentAccumP] = preg_replace('/'.CR.'?'.LF.'[ ]*$/', '', $contentAccum[$contentAccumP]);
						}
						$this->data[$this->currentValKey] = $contentAccum[$contentAccumP];
						$newInput=$this->cObjGetSingle($theName,$theConf,'/parseFunc/.tags.'.$tag[0]);	// fetch the content object

						$contentAccum[$contentAccumP]=$newInput;
						$contentAccumP++;

							// If the TypoTag section
						if (!$breakOut)	{
							$contentAccum[$contentAccumP-2].=$contentAccum[$contentAccumP-1].$contentAccum[$contentAccumP];
							unset($contentAccum[$contentAccumP]);
							unset($contentAccum[$contentAccumP-1]);
							$contentAccumP-=2;
						}

						unset($currentTag);
						$treated=1;
					}
						// other tags...
					if (!$treated)	{
						$contentAccum[$contentAccumP].=$data;
					}
				} else {
						// If a tag was not a typo tag, then it is just added to the content
					$stripNL = 0;
					if (t3lib_div::inList($allowTags,$tag[0]) || ($denyTags!='*' && !t3lib_div::inList($denyTags,$tag[0])))	{
						$contentAccum[$contentAccumP].=$data;
					} else {
						$contentAccum[$contentAccumP].=HTMLSpecialChars($data);
					}
				}
				$inside = 0;
			}
			$pointer+=$len;
		} while($pointer<$totalLen);

			// Parsing nonTypoTag content (all even keys):
		reset($contentAccum);
		for ($a=0;$a<count($contentAccum);$a++)	{
			if ($a%2 != 1)	{
					// stdWrap
				if (is_array($conf['nonTypoTagStdWrap.']))	{$contentAccum[$a] = $this->stdWrap($contentAccum[$a],$conf['nonTypoTagStdWrap.']);}
					// userFunc
				if ($conf['nonTypoTagUserFunc'])	{$contentAccum[$a] = $this->callUserFunction($conf['nonTypoTagUserFunc'], $conf['nonTypoTagUserFunc.'], $contentAccum[$a]);}
			}
		}
		return implode('',$contentAccum);
	}

	/**
	 * Lets you split the content by LF and proces each line independently. Used to format content made with the RTE.
	 *
	 * @param	string		The input value
	 * @param	array		TypoScript options
	 * @return	string		The processed input value being returned; Splitted lines imploded by LF again.
	 * @access private
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=323&cHash=a19312be78
	 */
	function encaps_lineSplit($theValue, $conf)	{
		$lParts = explode(LF,$theValue);

		$encapTags = t3lib_div::trimExplode(',',strtolower($conf['encapsTagList']),1);
		$nonWrappedTag = $conf['nonWrappedTag'];
		$defaultAlign=trim($this->stdWrap($conf['defaultAlign'],$conf['defaultAlign.']));

		if (!strcmp('',$theValue))	return '';

		foreach ($lParts as $k => $l) {
			$sameBeginEnd=0;
			$l=trim($l);
			$attrib=array();
			$nWrapped=0;
			$byPass=0;
			if (substr($l,0,1)=='<' && substr($l,-1)=='>')	{
				$fwParts = explode('>',substr($l,1),2);
				$backParts = t3lib_div::revExplode('<', substr($fwParts[1],0,-1), 2);
				$attrib = t3lib_div::get_tag_attributes('<'.$fwParts[0].'>');
				list($tagName) = explode(' ',$fwParts[0]);
				$str_content = $backParts[0];
				$sameBeginEnd = (substr(strtolower($backParts[1]),1,strlen($tagName))==strtolower($tagName));
			}

			if ($sameBeginEnd && in_array(strtolower($tagName),$encapTags))	{
				$uTagName = strtoupper($tagName);
				$uTagName = strtoupper($conf['remapTag.'][$uTagName]?$conf['remapTag.'][$uTagName]:$uTagName);
			} else {
				$uTagName = strtoupper($nonWrappedTag);
				$str_content = $lParts[$k];
				$nWrapped=1;
				$attrib=array();
			}

				// Wrapping all inner-content:
			if (is_array($conf['innerStdWrap_all.']))	{$str_content = $this->stdWrap($str_content,$conf['innerStdWrap_all.']);}

			if ($uTagName)	{
					// Setting common attributes
				if (is_array($conf['addAttributes.'][$uTagName.'.']))	{
					foreach ($conf['addAttributes.'][$uTagName.'.'] as $kk => $vv) {
						if (!is_array($vv))	{
							if ((string)$conf['addAttributes.'][$uTagName.'.'][$kk.'.']['setOnly']=='blank')	{
								if (!strcmp($attrib[$kk],''))	$attrib[$kk]=$vv;
							} elseif ((string)$conf['addAttributes.'][$uTagName.'.'][$kk.'.']['setOnly']=='exists')	{
								if (!isset($attrib[$kk]))	$attrib[$kk]=$vv;
							} else {
								$attrib[$kk]=$vv;
							}
						}
					}
				}
					// Wrapping all inner-content:
				if (is_array($conf['encapsLinesStdWrap.'][$uTagName.'.']))	{$str_content = $this->stdWrap($str_content,$conf['encapsLinesStdWrap.'][$uTagName.'.']);}
					// Default align
				if (!$attrib['align'] && $defaultAlign)	$attrib['align']=$defaultAlign;

				$params = t3lib_div::implodeAttributes($attrib,1);
				if ($conf['removeWrapping'])	{
					$str_content=$str_content;
				} else {
					$str_content='<'.strtolower($uTagName).(trim($params)?' '.trim($params):'').'>'.$str_content.'</'.strtolower($uTagName).'>';
				}
			}

			if ($nWrapped && $conf['wrapNonWrappedLines'])	{$str_content = $this->wrap($str_content,$conf['wrapNonWrappedLines']);}
			$lParts[$k] = $str_content;
		}

		return implode(LF,$lParts);
	}

	/**
	 * Finds URLS in text and makes it to a real link.
	 * Will find all strings prefixed with "http://" in the $data string and make them into a link, linking to the URL we should have found.
	 *
	 * @param	string		The string in which to search for "http://"
	 * @param	array		Configuration for makeLinks, see link
	 * @return	string		The processed input string, being returned.
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=328&cHash=c1135706d7
	 * @see _parseFunc()
	 */
	function http_makelinks($data,$conf)	{
		$aTagParams = $this->getATagParams($conf);
		$textpieces = explode('http://', $data);
		$pieces = count($textpieces);
		$textstr = $textpieces[0];
		$initP = '?id='.$GLOBALS['TSFE']->id.'&type='.$GLOBALS['TSFE']->type;
		for($i=1; $i<$pieces; $i++)	{
			$len=strcspn($textpieces[$i],chr(32).TAB.CRLF);
			if (trim(substr($textstr,-1))=='' && $len)	{

				$lastChar=substr($textpieces[$i],$len-1,1);
				if (!preg_match('/[A-Za-z0-9\/#_-]/',$lastChar)) {$len--;}		// Included '\/' 3/12

				$parts[0]=substr($textpieces[$i],0,$len);
				$parts[1]=substr($textpieces[$i],$len);

				$keep=$conf['keep'];
				$linkParts=parse_url('http://'.$parts[0]);
				$linktxt='';
				if (strstr($keep,'scheme'))	{
					$linktxt='http://';
				}
				$linktxt.= $linkParts['host'];
				if (strstr($keep,'path'))	{
					$linktxt.= $linkParts['path'];
					if (strstr($keep,'query') && $linkParts['query'])	{		// added $linkParts['query'] 3/12
						$linktxt.= '?'.$linkParts['query'];
					} elseif ($linkParts['path']=='/')	{  // If query is NOT added and the path is '/' then remove the slash ('/')   (added 3/12)
						$linktxt=substr($linktxt,0,-1);
					}
				}
  				$target = isset($conf['extTarget']) ? $conf['extTarget'] : $GLOBALS['TSFE']->extTarget;
				if ($GLOBALS['TSFE']->config['config']['jumpurl_enable'])	{
					$res = '<a'.
							' href="'.htmlspecialchars($GLOBALS['TSFE']->absRefPrefix.$GLOBALS['TSFE']->config['mainScript'].$initP.'&jumpurl='.rawurlencode('http://'.$parts[0]).$GLOBALS['TSFE']->getMethodUrlIdToken).'"'.
							($target ? ' target="'.$target.'"' : '').
							$aTagParams.
							$this->extLinkATagParams('http://'.$parts[0], 'url').
							'>';
				} else {
					$res = '<a'.
							' href="http://'.htmlspecialchars($parts[0]).'"'.
							($target ? ' target="'.$target.'"' : '').
							$aTagParams.
							$this->extLinkATagParams('http://'.$parts[0], 'url').
							'>';
				}
				if ($conf['ATagBeforeWrap'])	{
					$res= $res.$this->wrap($linktxt, $conf['wrap']).'</a>';
				} else {
					$res= $this->wrap($res.$linktxt.'</a>', $conf['wrap']);
				}
				$textstr.=$res.$parts[1];
			} else {
				$textstr.='http://'.$textpieces[$i];
			}
		}
		return $textstr;
	}

	/**
	 * Will find all strings prefixed with "mailto:" in the $data string and make them into a link, linking to the email address they point to.
	 *
	 * @param	string		The string in which to search for "mailto:"
	 * @param	array		Configuration for makeLinks, see link
	 * @return	string		The processed input string, being returned.
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=328&cHash=c1135706d7
	 * @see _parseFunc()
	 */
	function mailto_makelinks($data,$conf)	{
		// http-split
		$aTagParams = $this->getATagParams($conf);
		$textpieces = explode('mailto:', $data);
		$pieces = count($textpieces);
		$textstr = $textpieces[0];
		$initP = '?id='.$GLOBALS['TSFE']->id.'&type='.$GLOBALS['TSFE']->type;
		for($i=1; $i<$pieces; $i++)	{
			$len = strcspn($textpieces[$i],chr(32).TAB.CRLF);
			if (trim(substr($textstr,-1))=='' && $len)	{
				$lastChar = substr($textpieces[$i],$len-1,1);
				if (!preg_match('/[A-Za-z0-9]/',$lastChar)) {$len--;}

				$parts[0] = substr($textpieces[$i],0,$len);
				$parts[1] = substr($textpieces[$i],$len);
				$linktxt = preg_replace('/\?.*/','',$parts[0]);
				list($mailToUrl,$linktxt) = $this->getMailTo($parts[0],$linktxt,$initP);
				$mailToUrl = $GLOBALS['TSFE']->spamProtectEmailAddresses === 'ascii'?$mailToUrl:htmlspecialchars($mailToUrl);
				$res = '<a href="'.$mailToUrl.'"'.$aTagParams.'>';
				if ($conf['ATagBeforeWrap'])	{
					$res= $res.$this->wrap($linktxt, $conf['wrap']).'</a>';
				} else {
					$res= $this->wrap($res.$linktxt.'</a>', $conf['wrap']);
				}
				$textstr.=$res.$parts[1];
			} else {
				$textstr.='mailto:'.$textpieces[$i];
			}
		}
		return $textstr;
	}

	/**
	 * Creates and returns a TypoScript "imgResource".
	 * The value ($file) can either be a file reference (TypoScript resource) or the string "GIFBUILDER". In the first case a current image is returned, possibly scaled down or otherwise processed. In the latter case a GIFBUILDER image is returned; This means an image is made by TYPO3 from layers of elements as GIFBUILDER defines.
	 * In the function IMG_RESOURCE() this function is called like $this->getImgResource($conf['file'],$conf['file.']);
	 *
	 * @param	string		A "imgResource" TypoScript data type. Either a TypoScript file resource or the string GIFBUILDER. See description above.
	 * @param	array		TypoScript properties for the imgResource type
	 * @return	array		Returns info-array. info[origFile] = original file.
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=315&cHash=63b593a934
	 * @see IMG_RESOURCE(), cImage(), tslib_gifBuilder
	 */
	function getImgResource($file,$fileArray)	{
		if (is_array($fileArray))	{
			switch($file)	{
				case 'GIFBUILDER':
					$gifCreator = t3lib_div::makeInstance('tslib_gifbuilder');
					$gifCreator->init();
					$theImage='';
					if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib'])	{
						$gifCreator->start($fileArray,$this->data);
						$theImage = $gifCreator->gifBuild();
					}
					$imageResource = $gifCreator->getImageDimensions($theImage);
				break;
				default:
					if ($fileArray['import.'])	{
						$ifile = $this->stdWrap('',$fileArray['import.']);
						if ($ifile)	{$file = $fileArray['import'].$ifile;}
					}
					$theImage = $GLOBALS['TSFE']->tmpl->getFileName($file);
					if ($theImage)	{
						$fileArray['width']= $this->stdWrap($fileArray['width'],$fileArray['width.']);
						$fileArray['height']= $this->stdWrap($fileArray['height'],$fileArray['height.']);
						$fileArray['ext']= $this->stdWrap($fileArray['ext'],$fileArray['ext.']);
						$fileArray['maxW']= intval($this->stdWrap($fileArray['maxW'],$fileArray['maxW.']));
						$fileArray['maxH']= intval($this->stdWrap($fileArray['maxH'],$fileArray['maxH.']));
						$fileArray['minW']= intval($this->stdWrap($fileArray['minW'],$fileArray['minW.']));
						$fileArray['minH']= intval($this->stdWrap($fileArray['minH'],$fileArray['minH.']));
						$maskArray=	$fileArray['m.'];
						$maskImages=array();
						if (is_array($fileArray['m.']))	{	// Must render mask images and include in hash-calculating - else we cannot be sure the filename is unique for the setup!
							$maskImages['m_mask'] = $this->getImgResource($maskArray['mask'],$maskArray['mask.']);
							$maskImages['m_bgImg'] = $this->getImgResource($maskArray['bgImg'],$maskArray['bgImg.']);
							$maskImages['m_bottomImg'] = $this->getImgResource($maskArray['bottomImg'],$maskArray['bottomImg.']);
							$maskImages['m_bottomImg_mask'] = $this->getImgResource($maskArray['bottomImg_mask'],$maskArray['bottomImg_mask.']);
						}
						$hash = t3lib_div::shortMD5($theImage.serialize($fileArray).serialize($maskImages));
						if (!isset($GLOBALS['TSFE']->tmpl->fileCache[$hash]))	{
							$gifCreator = t3lib_div::makeInstance('tslib_gifbuilder');
							$gifCreator->init();

							if ($GLOBALS['TSFE']->config['config']['meaningfulTempFilePrefix'])	{
								$filename = basename($theImage);
									// remove extension
								$filename = substr($filename, 0, strrpos($filename, '.'));
									// strip everything non-ascii
								$filename = preg_replace('/[^A-Za-z0-9_-]/', '', trim($filename));
								$gifCreator->filenamePrefix = substr($filename, 0, intval($GLOBALS['TSFE']->config['config']['meaningfulTempFilePrefix'])) . '_';
								unset($filename);
							}

							if ($fileArray['sample'])	{
								$gifCreator->scalecmd = '-sample';
								$GLOBALS['TT']->setTSlogMessage('Sample option: Images are scaled with -sample.');
							}
							if ($fileArray['alternativeTempPath'] && t3lib_div::inList($GLOBALS['TYPO3_CONF_VARS']['FE']['allowedTempPaths'],$fileArray['alternativeTempPath']))	{
								$gifCreator->tempPath = $fileArray['alternativeTempPath'];
								$GLOBALS['TT']->setTSlogMessage('Set alternativeTempPath: '.$fileArray['alternativeTempPath']);
							}

							if (!trim($fileArray['ext'])){$fileArray['ext']='web';}
							$options = Array();
							if ($fileArray['maxW']) {$options['maxW']=$fileArray['maxW'];}
							if ($fileArray['maxH']) {$options['maxH']=$fileArray['maxH'];}
							if ($fileArray['minW']) {$options['minW']=$fileArray['minW'];}
							if ($fileArray['minH']) {$options['minH']=$fileArray['minH'];}

								// checks to see if m (the mask array) is defined
							if (is_array($maskArray) && $GLOBALS['TYPO3_CONF_VARS']['GFX']['im'])	{
									// Filename:
								$fI = t3lib_div::split_fileref($theImage);
								$imgExt = (strtolower($fI['fileext'])==$gifCreator->gifExtension ? $gifCreator->gifExtension : 'jpg');
								$dest = $gifCreator->tempPath.$hash.'.'.$imgExt;
								if (!file_exists($dest))	{		// Generate!
									$m_mask= $maskImages['m_mask'];
									$m_bgImg = $maskImages['m_bgImg'];
									if ($m_mask && $m_bgImg)	{
										$negate = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_negate_mask'] ? ' -negate' : '';

										$temp_ext='png';
										if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_mask_temp_ext_gif'])	{		// If ImageMagick version 5+
											$temp_ext=$gifCreator->gifExtension;
										}

										$tempFileInfo = $gifCreator->imageMagickConvert($theImage,$temp_ext,$fileArray['width'],$fileArray['height'],$fileArray['params'],$fileArray['frame'],$options);
										if (is_array($tempFileInfo))	{
											$m_bottomImg = $maskImages['m_bottomImg'];
											if ($m_bottomImg)	{
												$m_bottomImg_mask = $maskImages['m_bottomImg_mask'];
											}
												//	Scaling:	****
											$tempScale=array();
											$command = '-geometry '.$tempFileInfo[0].'x'.$tempFileInfo[1].'!';
											$command = $this->modifyImageMagickStripProfileParameters($command, $fileArray);
											$tmpStr = $gifCreator->randomName();

												//	m_mask
											$tempScale['m_mask']=$tmpStr.'_mask.'.$temp_ext;
											$gifCreator->imageMagickExec($m_mask[3],$tempScale['m_mask'],$command.$negate);
												//	m_bgImg
											$tempScale['m_bgImg']=$tmpStr.'_bgImg.'.trim($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_mask_temp_ext_noloss']);
											$gifCreator->imageMagickExec($m_bgImg[3],$tempScale['m_bgImg'],$command);

												//	m_bottomImg / m_bottomImg_mask
											if ($m_bottomImg && $m_bottomImg_mask)	{
												$tempScale['m_bottomImg']=$tmpStr.'_bottomImg.'.$temp_ext;
												$gifCreator->imageMagickExec($m_bottomImg[3],$tempScale['m_bottomImg'],$command);
												$tempScale['m_bottomImg_mask']=$tmpStr.'_bottomImg_mask.'.$temp_ext;
												$gifCreator->imageMagickExec($m_bottomImg_mask[3],$tempScale['m_bottomImg_mask'],$command.$negate);

													// BEGIN combining:
													// The image onto the background
												$gifCreator->combineExec($tempScale['m_bgImg'],$tempScale['m_bottomImg'],$tempScale['m_bottomImg_mask'],$tempScale['m_bgImg']);
											}
												// The image onto the background
											$gifCreator->combineExec($tempScale['m_bgImg'],$tempFileInfo[3],$tempScale['m_mask'],$dest);
												// Unlink the temp-images...
											foreach ($tempScale as $file) {
												if (@is_file($file))	{
													unlink($file);
												}
											}
												//	t3lib_div::print_array($GLOBALS['TSFE']->tmpl->fileCache[$hash]);
										}
									}
								}
									// Finish off
								if (($fileArray['reduceColors'] || ($imgExt=='png' && !$gifCreator->png_truecolor)) && is_file($dest))	{
									$reduced = $gifCreator->IMreduceColors($dest, t3lib_div::intInRange($fileArray['reduceColors'], 256, $gifCreator->truecolorColors, 256));
									if (is_file($reduced))	{
										unlink($dest);
										rename($reduced, $dest);
									}
								}
								$GLOBALS['TSFE']->tmpl->fileCache[$hash]= $gifCreator->getImageDimensions($dest);
							} else {		// Normal situation:
								$fileArray['params'] = $this->modifyImageMagickStripProfileParameters($fileArray['params'], $fileArray);
								$GLOBALS['TSFE']->tmpl->fileCache[$hash]= $gifCreator->imageMagickConvert($theImage,$fileArray['ext'],$fileArray['width'],$fileArray['height'],$fileArray['params'],$fileArray['frame'],$options);
								if (($fileArray['reduceColors'] || ($imgExt=='png' && !$gifCreator->png_truecolor)) && is_file($GLOBALS['TSFE']->tmpl->fileCache[$hash][3]))	{
									$reduced = $gifCreator->IMreduceColors($GLOBALS['TSFE']->tmpl->fileCache[$hash][3], t3lib_div::intInRange($fileArray['reduceColors'], 256, $gifCreator->truecolorColors, 256));
									if (is_file($reduced))	{
										unlink($GLOBALS['TSFE']->tmpl->fileCache[$hash][3]);
										rename($reduced, $GLOBALS['TSFE']->tmpl->fileCache[$hash][3]);
									}
								}
							}
							$GLOBALS['TSFE']->tmpl->fileCache[$hash]['origFile'] = $theImage;
							$GLOBALS['TSFE']->tmpl->fileCache[$hash]['origFile_mtime'] = @filemtime($theImage);	// This is needed by tslib_gifbuilder, ln 100ff in order for the setup-array to create a unique filename hash.
							$GLOBALS['TSFE']->tmpl->fileCache[$hash]['fileCacheHash'] = $hash;
						}
						$imageResource = $GLOBALS['TSFE']->tmpl->fileCache[$hash];
					}

				break;
			}
		}
		$theImage = $GLOBALS['TSFE']->tmpl->getFileName($file);
			// If image was processed by GIFBUILDER:
			// ($imageResource indicates that it was processed the regular way)
		if (!isset($imageResource) && $theImage) {
			$gifCreator = t3lib_div::makeInstance('tslib_gifbuilder');
			/* @var $gifCreator tslib_gifbuilder */
			$gifCreator->init();
			$info= $gifCreator->imageMagickConvert($theImage,'WEB','','','','','');
			$info['origFile'] = $theImage;
			$info['origFile_mtime'] = @filemtime($theImage);	// This is needed by tslib_gifbuilder, ln 100ff in order for the setup-array to create a unique filename hash.
			$imageResource = $info;
		}

			// Hook 'getImgResource': Post-processing of image resources
		if (isset($imageResource)) {
			foreach($this->getGetImgResourceHookObjects() as $hookObject) {
				$imageResource = $hookObject->getImgResourcePostProcess($file, (array)$fileArray, $imageResource, $this);
			}
		}

		return $imageResource;
	}

	/**
	 * Modifies the parameters for ImageMagick for stripping of profile information.
	 *
	 * @param	string		$parameters: The parameters to be modified (if required)
	 * @param	array		$configuration: The TypoScript configuration of [IMAGE].file
	 * @param	string		The modified parameters
	 */
	protected function modifyImageMagickStripProfileParameters($parameters, array $configuration) {
			// Strips profile information of image to save some space:
		if (isset($configuration['stripProfile'])) {
			if ($configuration['stripProfile']) {
				$parameters = $gfxConf['im_stripProfileCommand'] . $parameters;
			} else {
				$parameters.= '###SkipStripProfile###';
			}
		}
		return $parameters;
	}






















	/***********************************************
	 *
	 * Data retrieval etc.
	 *
	 ***********************************************/


	/**
	 * Returns the value for the field from $this->data. If "//" is found in the $field value that token will split the field values apart and the first field having a non-blank value will be returned.
	 *
	 * @param	string		The fieldname, eg. "title" or "navtitle // title" (in the latter case the value of $this->data[navtitle] is returned if not blank, otherwise $this->data[title] will be)
	 * @return	string
	 */
	function getFieldVal($field)	{
		if (!strstr($field,'//'))	{
			return $this->data[trim($field)];
		} else {
			$sections = t3lib_div::trimExplode('//',$field,1);
			foreach ($sections as $k) {
				if (strcmp($this->data[$k],''))	return $this->data[$k];
			}
		}
	}

	/**
	 * Implements the TypoScript data type "getText". This takes a string with parameters and based on those a value from somewhere in the system is returned.
	 *
	 * @param	string		The parameter string, eg. "field : title" or "field : navtitle // field : title" (in the latter case and example of how the value is FIRST splitted by "//" is shown)
	 * @param	mixed		Alternative field array; If you set this to an array this variable will be used to look up values for the "field" key. Otherwise the current page record in $GLOBALS['TSFE']->page is used.
	 * @return	string		The value fetched
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=282&cHash=831a95115d
	 * @see getFieldVal()
	 */
	function getData($string,$fieldArray)	{
		global $TYPO3_CONF_VARS;

		if (!is_array($fieldArray))	{
			$fieldArray=$GLOBALS['TSFE']->page;
		}
		$retVal = '';
		$sections = explode('//',$string);

		while (!$retVal AND list($secKey, $secVal)=each($sections)) {
			$parts = explode(':',$secVal,2);
			$key = trim($parts[1]);
			if ((string)$key!='')	{
				$type = strtolower(trim($parts[0]));
				switch($type) {
					case 'gpvar':
						t3lib_div::deprecationLog('Using gpvar in TypoScript getText is deprecated since TYPO3 4.3 - Use gp instead of gpvar.');
						// Fall Through
					case 'gp':
							// Merge GET and POST and get $key out of the merged array
						$retVal = $this->getGlobal(
							$key,
							t3lib_div::array_merge_recursive_overrule(t3lib_div::_GET(), t3lib_div::_POST())
						);
					break;
					case 'tsfe':
						$retVal = $this->getGlobal ('TSFE|' . $key);
					break;
					case 'getenv':
						$retVal = getenv($key);
					break;
					case 'getindpenv':
						$retVal = t3lib_div::getIndpEnv($key);
					break;
					case 'field':
						$retVal = $fieldArray[$key];
					break;
					case 'parameters':
						$retVal = $this->parameters[$key];
					break;
					case 'register':
						$retVal = $GLOBALS['TSFE']->register[$key];
					break;
					case 'global':
						$retVal = $this->getGlobal($key);
					break;
					case 'leveltitle':
						$nkey = $this->getKey($key,$GLOBALS['TSFE']->tmpl->rootLine);
						$retVal = $this->rootLineValue($nkey,'title',stristr($key,'slide'));
					break;
					case 'levelmedia':
						$nkey = $this->getKey($key,$GLOBALS['TSFE']->tmpl->rootLine);
						$retVal = $this->rootLineValue($nkey,'media',stristr($key,'slide'));
					break;
					case 'leveluid':
						$nkey = $this->getKey($key,$GLOBALS['TSFE']->tmpl->rootLine);
						$retVal = $this->rootLineValue($nkey,'uid',stristr($key,'slide'));
					break;
					case 'levelfield':
						$keyP = t3lib_div::trimExplode(',',$key);
						$nkey = $this->getKey($keyP[0],$GLOBALS['TSFE']->tmpl->rootLine);
						$retVal = $this->rootLineValue($nkey,$keyP[1],strtolower($keyP[2])=='slide');
					break;
					case 'fullrootline':
						$keyP = t3lib_div::trimExplode(',',$key);
						$fullKey = intval($keyP[0])-count($GLOBALS['TSFE']->tmpl->rootLine)+count($GLOBALS['TSFE']->rootLine);
						if ($fullKey>=0)	{
							$retVal = $this->rootLineValue($fullKey,$keyP[1],stristr($keyP[2],'slide'),$GLOBALS['TSFE']->rootLine);
						}
					break;
					case 'date':
						if (!$key) {$key = 'd/m Y';}
						$retVal = date($key, $GLOBALS['EXEC_TIME']);
					break;
					case 'page':
						$retVal = $GLOBALS['TSFE']->page[$key];
					break;
					case 'current':
						$retVal = $this->data[$this->currentValKey];
					break;
					case 'level':
						$retVal = count($GLOBALS['TSFE']->tmpl->rootLine)-1;
					break;
					case 'db':
						$selectParts = t3lib_div::trimExplode(':',$key);
						$db_rec = $GLOBALS['TSFE']->sys_page->getRawRecord($selectParts[0],$selectParts[1]);
						if (is_array($db_rec) && $selectParts[2])	{$retVal = $db_rec[$selectParts[2]];}
					break;
					case 'lll':
						$retVal = $GLOBALS['TSFE']->sL('LLL:'.$key);
					break;
					case 'path':
						$retVal = $GLOBALS['TSFE']->tmpl->getFileName($key);
					break;
					case 'cobj':
						switch((string)$key)	{
							case 'parentRecordNumber':
								$retVal = $this->parentRecordNumber;
							break;
						}
					break;
					case 'debug':
						switch((string)$key)	{
							case 'rootLine':
								$retVal = t3lib_div::view_array($GLOBALS['TSFE']->tmpl->rootLine);
							break;
							case 'fullRootLine':
								$retVal = t3lib_div::view_array($GLOBALS['TSFE']->rootLine);
							break;
							case 'data':
								$retVal = t3lib_div::view_array($this->data);
							break;
						}
					break;
				}
			}

			if(is_array($TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_content.php']['getData']))    {
				foreach($TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_content.php']['getData'] as $classData)    {
					$hookObject = t3lib_div::getUserObj($classData);

					if(!($hookObject instanceof tslib_content_getDataHook)) {
						throw new UnexpectedValueException('$hookObject must implement interface tslib_content_getDataHook', 1195044480);
					}

					$retVal = $hookObject->getDataExtension($string, $fieldArray, $secVal, $retVal, $this);
				}
			}
		}

		return $retVal;
	}

	/**
	 * Returns a value from the current rootline (site) from $GLOBALS['TSFE']->tmpl->rootLine;
	 *
	 * @param	string		Which level in the root line
	 * @param	string		The field in the rootline record to return (a field from the pages table)
	 * @param	boolean		If set, then we will traverse through the rootline from outer level towards the root level until the value found is true
	 * @param	mixed		If you supply an array for this it will be used as an alternative root line array
	 * @return	string		The value from the field of the rootline.
	 * @access private
	 * @see getData()
	 */
	function rootLineValue($key,$field,$slideBack=0,$altRootLine='')	{
		$rootLine = is_array($altRootLine) ? $altRootLine : $GLOBALS['TSFE']->tmpl->rootLine;
		if (!$slideBack)	{
			return $rootLine[$key][$field];
		} else {
			for ($a=$key;$a>=0;$a--)	{
				$val = $rootLine[$a][$field];
				if ($val)	{return $val;}
			}
		}
	}

	/**
	 * Return global variable where the input string $var defines array keys separated by "|"
	 * Example: $var = "HTTP_SERVER_VARS | something" will return the value $GLOBALS['HTTP_SERVER_VARS']['something'] value
	 *
	 * @param	string		Global var key, eg. "HTTP_GET_VAR" or "HTTP_GET_VARS|id" to get the GET parameter "id" back.
	 * @param	array		Alternative array than $GLOBAL to get variables from.
	 * @return	mixed		Whatever value. If none, then blank string.
	 * @see getData()
	 */
	function getGlobal($keyString, $source = NULL) {
		$keys = explode('|', $keyString);
		$numberOfLevels = count($keys);
		$rootKey = trim($keys[0]);
		$value = isset($source) ? $source[$rootKey] : $GLOBALS[$rootKey];

		for ($i = 1; $i < $numberOfLevels && isset($value); $i++) {
			$currentKey = trim($keys[$i]);
			if (is_object($value)) {
				$value = $value->$currentKey;
			} elseif (is_array($value)) {
				$value = $value[$currentKey];
			} else {
				$value = '';
				break;
			}
		}

		if (!is_scalar($value)) {
			$value = '';
		}
		return $value;
	}

	/**
	 * Processing of key values pointing to entries in $arr; Here negative values are converted to positive keys pointer to an entry in the array but from behind (based on the negative value).
	 * Example: entrylevel = -1 means that entryLevel ends up pointing at the outermost-level, -2 means the level before the outermost...
	 *
	 * @param	integer		The integer to transform
	 * @param	array		Array in which the key should be found.
	 * @return	integer		The processed integer key value.
	 * @access private
	 * @see getData()
	 */
	function getKey($key,$arr)	{
		$key = intval($key);
		if (is_array($arr))	{
			if ($key < 0)	{
				$key = count($arr)+$key;
			}
			if ($key < 0)	{
				$key=0;
			}
		}
		return $key;
	}


	/**
	 * Looks up the incoming value in the defined TCA configuration
	 * Works only with TCA-type 'select' and options defined in 'items'
	 *
	 * @param	mixed		Comma-separated list of values to look up
	 * @param	array		TS-configuration array, see TSref for details
	 * @return	string		String of translated values, seperated by $delimiter. If no matches were found, the input value is simply returned.
	 * @todo	It would be nice it this function basically looked up any type of value, db-relations etc.
	 */
	function TCAlookup($inputValue,$conf)	{
		global $TCA;

		$table = $conf['table'];
		$field = $conf['field'];
		$delimiter = $conf['delimiter']?$conf['delimiter']:' ,';

		$GLOBALS['TSFE']->includeTCA();

		if (is_array($TCA[$table]) && is_array($TCA[$table]['columns'][$field]) && is_array($TCA[$table]['columns'][$field]['config']['items'])) {
			$values = t3lib_div::trimExplode(',',$inputValue);
			$output = array();
			foreach ($values as $value) {
					// Traverse the items-array...
				foreach ($TCA[$table]['columns'][$field]['config']['items'] as $item) {
						// ... and return the first found label where the value was equal to $key
					if (!strcmp($item[1],trim($value))) {
						$output[] = $GLOBALS['TSFE']->sL($item[0]);
					}
				}
			}
			$returnValue = implode($delimiter,$output);
		} else {
			$returnValue = $inputValue;
		}
		return $returnValue;
	}















	/***********************************************
	 *
	 * Link functions (typolink)
	 *
	 ***********************************************/


	/**
	 * Implements the "typolink" property of stdWrap (and others)
	 * Basically the input string, $linktext, is (typically) wrapped in a <a>-tag linking to some page, email address, file or URL based on a parameter defined by the configuration array $conf.
	 * This function is best used from internal functions as is. There are some API functions defined after this function which is more suited for general usage in external applications.
	 * Generally the concept "typolink" should be used in your own applications as an API for making links to pages with parameters and more. The reason for this is that you will then automatically make links compatible with all the centralized functions for URL simulation and manipulation of parameters into hashes and more.
	 * For many more details on the parameters and how they are intepreted, please see the link to TSref below.
	 *
	 * @param	string		The string (text) to link
	 * @param	array		TypoScript configuration (see link below)
	 * @return	string		A link-wrapped string.
	 * @see stdWrap(), tslib_pibase::pi_linkTP()
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=321&cHash=59bd727a5e
	 */
	function typoLink($linktxt, $conf)	{
		$LD = array();
		$finalTagParts = array();
		$finalTagParts['aTagParams'] = $this->getATagParams($conf);

		$link_param = trim($this->stdWrap($conf['parameter'],$conf['parameter.']));

		$sectionMark = trim($this->stdWrap($conf['section'],$conf['section.']));
		$sectionMark = $sectionMark ? (t3lib_div::testInt($sectionMark)?'#c':'#').$sectionMark : '';
		$initP = '?id='.$GLOBALS['TSFE']->id.'&type='.$GLOBALS['TSFE']->type;
		$this->lastTypoLinkUrl = '';
		$this->lastTypoLinkTarget = '';
		if ($link_param) {
			$enableLinksAcrossDomains = $GLOBALS['TSFE']->config['config']['typolinkEnableLinksAcrossDomains'];
			$link_paramA = t3lib_div::unQuoteFilenames($link_param,true);

				// Check for link-handler keyword:
			list($linkHandlerKeyword,$linkHandlerValue) = explode(':',trim($link_paramA[0]),2);
			if ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler'][$linkHandlerKeyword] && strcmp($linkHandlerValue, '')) {
				$linkHandlerObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler'][$linkHandlerKeyword]);

				if(method_exists($linkHandlerObj, 'main')) {
					return $linkHandlerObj->main($linktxt, $conf, $linkHandlerKeyword, $linkHandlerValue, $link_param, $this);
				}
			}

			$link_param = trim($link_paramA[0]);	// Link parameter value
			$linkClass = trim($link_paramA[2]);		// Link class
			if ($linkClass=='-')	$linkClass = '';	// The '-' character means 'no class'. Necessary in order to specify a title as fourth parameter without setting the target or class!
			$forceTarget = trim($link_paramA[1]);	// Target value
			$forceTitle = trim($link_paramA[3]);	// Title value
			if ($forceTarget=='-')	$forceTarget = '';	// The '-' character means 'no target'. Necessary in order to specify a class as third parameter without setting the target!
				// Check, if the target is coded as a JS open window link:
			$JSwindowParts = array();
			$JSwindowParams = '';
			$onClick = '';
			if ($forceTarget && preg_match('/^([0-9]+)x([0-9]+)(:(.*)|.*)$/',$forceTarget,$JSwindowParts))	{
					// Take all pre-configured and inserted parameters and compile parameter list, including width+height:
				$JSwindow_tempParamsArr = t3lib_div::trimExplode(',',strtolower($conf['JSwindow_params'].','.$JSwindowParts[4]),1);
				$JSwindow_paramsArr=array();
				foreach($JSwindow_tempParamsArr as $JSv)	{
					list($JSp,$JSv) = explode('=',$JSv);
					$JSwindow_paramsArr[$JSp]=$JSp.'='.$JSv;
				}
					// Add width/height:
				$JSwindow_paramsArr['width']='width='.$JSwindowParts[1];
				$JSwindow_paramsArr['height']='height='.$JSwindowParts[2];
					// Imploding into string:
				$JSwindowParams = implode(',',$JSwindow_paramsArr);
				$forceTarget = '';	// Resetting the target since we will use onClick.
			}

				// Internal target:
			$target = isset($conf['target']) ? $conf['target'] : $GLOBALS['TSFE']->intTarget;
			if ($conf['target.'])	{$target=$this->stdWrap($target, $conf['target.']);}

				// Title tag
			$title = $conf['title'];
			if ($conf['title.'])	{$title=$this->stdWrap($title, $conf['title.']);}

				// Parse URL:
			$pU = parse_url($link_param);

				// Detecting kind of link:
			if(strstr($link_param,'@') && (!$pU['scheme'] || $pU['scheme']=='mailto'))	{		// If it's a mail address:
				$link_param = preg_replace('/^mailto:/i','',$link_param);
				list($this->lastTypoLinkUrl,$linktxt) = $this->getMailTo($link_param,$linktxt,$initP);
				$finalTagParts['url']=$this->lastTypoLinkUrl;
				$finalTagParts['TYPE']='mailto';
			} else {
				$isLocalFile=0;
				$fileChar=intval(strpos($link_param, '/'));
				$urlChar=intval(strpos($link_param, '.'));

				// Firsts, test if $link_param is numeric and page with such id exists. If yes, do not attempt to link to file
				if (!t3lib_div::testInt($link_param) || count($GLOBALS['TSFE']->sys_page->getPage_noCheck($link_param)) == 0) {
					// Detects if a file is found in site-root (or is a 'virtual' simulateStaticDocument file!) and if so it will be treated like a normal file.
					list($rootFileDat) = explode('?',rawurldecode($link_param));
					$containsSlash = strstr($rootFileDat,'/');
					$rFD_fI = pathinfo($rootFileDat);
					if (trim($rootFileDat) && !$containsSlash && (@is_file(PATH_site.$rootFileDat) || t3lib_div::inList('php,html,htm',strtolower($rFD_fI['extension']))))	{
						$isLocalFile = 1;
					} elseif ($containsSlash)	{
						$isLocalFile = 2;		// Adding this so realurl directories are linked right (non-existing).
					}
				}

				if($pU['scheme'] || ($isLocalFile!=1 && $urlChar && (!$containsSlash || $urlChar<$fileChar)))	{	// url (external): If doubleSlash or if a '.' comes before a '/'.
					$target = isset($conf['extTarget']) ? $conf['extTarget'] : $GLOBALS['TSFE']->extTarget;
					if ($conf['extTarget.'])	{$target = $this->stdWrap($target, $conf['extTarget.']);}
					if ($forceTarget)	{$target=$forceTarget;}
					if ($linktxt=='') $linktxt = $link_param;
					if (!$pU['scheme'])	{$scheme='http://';} else {$scheme='';}
					if ($GLOBALS['TSFE']->config['config']['jumpurl_enable'])	{
						$this->lastTypoLinkUrl = $GLOBALS['TSFE']->absRefPrefix.$GLOBALS['TSFE']->config['mainScript'].$initP.'&jumpurl='.rawurlencode($scheme.$link_param).$GLOBALS['TSFE']->getMethodUrlIdToken;
					} else {
						$this->lastTypoLinkUrl = $scheme.$link_param;
					}
					$this->lastTypoLinkTarget = $target;
					$finalTagParts['url']=$this->lastTypoLinkUrl;
					$finalTagParts['targetParams'] = $target ? ' target="'.$target.'"' : '';
					$finalTagParts['TYPE']='url';
					$finalTagParts['aTagParams'].=$this->extLinkATagParams($finalTagParts['url'], $finalTagParts['TYPE']);
				} elseif ($containsSlash || $isLocalFile)	{	// file (internal)
					$splitLinkParam = explode('?', $link_param);
					if (file_exists(rawurldecode($splitLinkParam[0])) || $isLocalFile)	{
						if ($linktxt=='') $linktxt = rawurldecode($link_param);
						if ($GLOBALS['TSFE']->config['config']['jumpurl_enable'])	{
							$this->lastTypoLinkUrl = $GLOBALS['TSFE']->absRefPrefix.$GLOBALS['TSFE']->config['mainScript'].$initP.'&jumpurl='.rawurlencode($link_param).$GLOBALS['TSFE']->getMethodUrlIdToken;
						} else {
							$this->lastTypoLinkUrl = $GLOBALS['TSFE']->absRefPrefix.$link_param;
						}
						$this->lastTypoLinkUrl = $this->forceAbsoluteUrl($this->lastTypoLinkUrl, $conf);
						$target = isset($conf['fileTarget']) ? $conf['fileTarget'] : $GLOBALS['TSFE']->fileTarget;
						if ($conf['fileTarget.'])	{$target = $this->stdWrap($target, $conf['fileTarget.']);}
						if ($forceTarget)	{$target=$forceTarget;}
						$this->lastTypoLinkTarget = $target;

						$finalTagParts['url'] = $this->lastTypoLinkUrl;
						$finalTagParts['targetParams'] = $target ? ' target="'.$target.'"' : '';
						$finalTagParts['TYPE'] = 'file';
						$finalTagParts['aTagParams'].=$this->extLinkATagParams($finalTagParts['url'], $finalTagParts['TYPE']);
					} else {
						$GLOBALS['TT']->setTSlogMessage("typolink(): File '".$splitLinkParam[0]."' did not exist, so '".$linktxt."' was not linked.",1);
						return $linktxt;
					}
	 			} else {	// integer or alias (alias is without slashes or periods or commas, that is 'nospace,alphanum_x,lower,unique' according to definition in $TCA!)
					if ($conf['no_cache.'])	$conf['no_cache']=$this->stdWrap($conf['no_cache'], $conf['no_cache.']);
						// Splitting the parameter by ',' and if the array counts more than 1 element it's a id/type/parameters triplet
					$pairParts = t3lib_div::trimExplode(',', $link_param, TRUE);
					$link_param = $pairParts[0];
					$link_params_parts = explode('#', $link_param);
					$link_param = trim($link_params_parts[0]);		// Link-data del
					if (!strcmp($link_param,''))	{$link_param=$GLOBALS['TSFE']->id;}	// If no id or alias is given
					if ($link_params_parts[1] && !$sectionMark)	{
						$sectionMark = trim($link_params_parts[1]);
						$sectionMark = (t3lib_div::testInt($sectionMark)?'#c':'#').$sectionMark;
					}
					unset($theTypeP);
					if (count($pairParts)>1)	{
						$theTypeP = isset($pairParts[1]) ? $pairParts[1] : 0;		// Overruling 'type'
						$conf['additionalParams'].= isset($pairParts[2]) ? $pairParts[2] : '';
					}
						// Checking if the id-parameter is an alias.
					if (!t3lib_div::testInt($link_param))	{
						$link_param = $GLOBALS['TSFE']->sys_page->getPageIdFromAlias($link_param);
					}

						// Link to page even if access is missing?
					if (strlen($conf['linkAccessRestrictedPages'])) {
						$disableGroupAccessCheck = ($conf['linkAccessRestrictedPages'] ? TRUE : FALSE);
					} else {
						$disableGroupAccessCheck = ($GLOBALS['TSFE']->config['config']['typolinkLinkAccessRestrictedPages'] ? TRUE : FALSE);
					}

						// Looking up the page record to verify its existence:
					$page = $GLOBALS['TSFE']->sys_page->getPage($link_param,$disableGroupAccessCheck);

					if (count($page))	{
							// MointPoints, look for closest MPvar:
						$MPvarAcc = array();
						if (!$GLOBALS['TSFE']->config['config']['MP_disableTypolinkClosestMPvalue'])	{
							$temp_MP = $this->getClosestMPvalueForPage($page['uid'],TRUE);
							if ($temp_MP)	$MPvarAcc['closest'] = $temp_MP;
						}
							// Look for overlay Mount Point:
						$mount_info = $GLOBALS['TSFE']->sys_page->getMountPointInfo($page['uid'], $page);
						if (is_array($mount_info) && $mount_info['overlay'])	{
							$page = $GLOBALS['TSFE']->sys_page->getPage($mount_info['mount_pid'],$disableGroupAccessCheck);
							if (!count($page))	{
								$GLOBALS['TT']->setTSlogMessage("typolink(): Mount point '".$mount_info['mount_pid']."' was not available, so '".$linktxt."' was not linked.",1);
								return $linktxt;
							}
							$MPvarAcc['re-map'] = $mount_info['MPvar'];
						}

							// Setting title if blank value to link:
						if ($linktxt=='') $linktxt = $page['title'];

							// Query Params:
						$addQueryParams = $conf['addQueryString'] ? $this->getQueryArguments($conf['addQueryString.']) : '';
						$addQueryParams .= trim($this->stdWrap($conf['additionalParams'],$conf['additionalParams.']));
						if (substr($addQueryParams,0,1)!='&')		{
							$addQueryParams = '';
						}
						if ($conf['useCacheHash']) {
								// Mind the order below! See http://bugs.typo3.org/view.php?id=5117
							$params = $GLOBALS['TSFE']->linkVars . $addQueryParams;
							if ($params) {
								$addQueryParams .= '&cHash=' . t3lib_div::generateCHash($params);
							}
							unset($params);
						}

						$targetDomain = '';
						$currentDomain = t3lib_div::getIndpEnv('HTTP_HOST');
						// Mount pages are always local and never link to another domain
						if (count($MPvarAcc))	{
							// Add "&MP" var:
							$addQueryParams.= '&MP='.rawurlencode(implode(',',$MPvarAcc));
						}
						elseif (strpos($addQueryParams, '&MP=') === false && $GLOBALS['TSFE']->config['config']['typolinkCheckRootline']) {

							// We do not come here if additionalParams had '&MP='. This happens when typoLink is called from
							// menu. Mount points always work in the content of the current domain and we must not change
							// domain if MP variables exist.

							// If we link across domains and page is free type shortcut, we must resolve the shortcut first!
							// If we do not do it, TYPO3 will fail to (1) link proper page in RealURL/CoolURI because
							// they return relative links and (2) show proper page if no RealURL/CoolURI exists when link is clicked
							if ($enableLinksAcrossDomains && $page['doktype'] == 4 && $page['shortcut_mode'] == 0) {
								$page2 = $page;	// Save in case of broken destination or endless loop
								$maxLoopCount = 20;	// Same as in RealURL, seems enough
								while ($maxLoopCount && is_array($page) && $page['doktype'] == 4 && $page['shortcut_mode'] == 0) {
									$page = $GLOBALS['TSFE']->sys_page->getPage($page['shortcut'], $disableGroupAccessCheck);
									$maxLoopCount--;
								}
								if (count($page) == 0 || $maxLoopCount == 0) {
									// We revert if shortcut is broken or maximum number of loops is exceeded (indicates endless loop)
									$page = $page2;
								}
							}

							// Find all domain records in the rootline of the target page
							$targetPageRootline = $GLOBALS['TSFE']->sys_page->getRootLine($page['uid']);
							$foundDomains = array();
							$firstFoundDomains = array();
							$firstFoundForcedDomains = array();
							$targetPageRootlinePids = array();
							foreach ($targetPageRootline as $data)	{
								$targetPageRootlinePids[] = intval($data['uid']);
							}
							$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
								'pid, domainName, forced',
								'sys_domain',
								'pid IN (' . implode(',', $targetPageRootlinePids) . ') ' .
									' AND redirectTo=\'\' ' . $this->enableFields('sys_domain'),
								'',
								'sorting ASC'
							);
							// TODO maybe it makes sense to hold all sys_domain records in a cache to save additional DB querys on each typolink
							while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
								$foundDomains[] = preg_replace('/\/$/', '', $row['domainName']);
								if (!isset($firstFoundDomains[$row['pid']])) {
									$firstFoundDomains[$row['pid']] = preg_replace('/\/$/', '', $row['domainName']);
								}
								if ($row['forced'] && !isset($firstFoundForcedDomains[$row['pid']])) {
									$firstFoundForcedDomains[$row['pid']] = preg_replace('/\/$/', '', $row['domainName']);
								}
							}
							$GLOBALS['TYPO3_DB']->sql_free_result($res);

							// Set targetDomain to first found domain record if the target page cannot be reached within the current domain
							if (count($foundDomains) > 0
							  && (!in_array($currentDomain, $foundDomains) || count($firstFoundForcedDomains) > 0)) {
								foreach ($targetPageRootlinePids as $pid) {
									// Always use the 'forced' domain if we found one
									if (isset($firstFoundForcedDomains[$pid])) {
										$targetDomain = $firstFoundForcedDomains[$pid];
										break;
									}
									// Use the first found domain record
									if ($targetDomain === '' && isset($firstFoundDomains[$pid])) {
										$targetDomain = $firstFoundDomains[$pid];
									}
								}
								// Do not prepend the domain if its the current hostname
								if ($targetDomain === $currentDomain) {
									$targetDomain = '';
								}
							}
						}

						$absoluteUrlScheme = 'http';
						// URL shall be absolute:
						if (isset($conf['forceAbsoluteUrl']) && $conf['forceAbsoluteUrl']) {
							// If no domain records are defined, use current domain:
							if ($targetDomain === '') {
								$targetDomain = $currentDomain;
							}
							// Override scheme:
							if (isset($conf['forceAbsoluteUrl.']['scheme']) && $conf['forceAbsoluteUrl.']['scheme']) {
								$absoluteUrlScheme = $conf['forceAbsoluteUrl.']['scheme'];
							}
						}

						// If target page has a different domain and the current domain's linking scheme (e.g. simulateStaticDocuments/RealURL/...) should not be used
						if (strlen($targetDomain) && $targetDomain !== $currentDomain && !$enableLinksAcrossDomains) {
							$target = isset($conf['extTarget']) ? $conf['extTarget'] : $GLOBALS['TSFE']->extTarget;
							if ($conf['extTarget.']) {
								$target = $this->stdWrap($target, $conf['extTarget.']);
							}
							if ($forceTarget) {
								$target = $forceTarget;
							}
							$LD['target'] = $target;
							$this->lastTypoLinkUrl = $this->URLqMark($absoluteUrlScheme . '://' . $targetDomain . '/index.php?id=' . $page['uid'], $addQueryParams) . $sectionMark;
						} else {	// Internal link or current domain's linking scheme should be used
							if ($forceTarget) {
								$target = $forceTarget;
							}
							$LD = $GLOBALS['TSFE']->tmpl->linkData($page, $target, $conf['no_cache'], '', '', $addQueryParams, $theTypeP, $targetDomain);
							if (strlen($targetDomain)) {
								// We will add domain only if URL does not have it already.

								if ($enableLinksAcrossDomains) {
									// Get rid of the absRefPrefix if necessary. absRefPrefix is applicable only
									// to the current web site. If we have domain here it means we link across
									// domains. absRefPrefix can contain domain name, which will screw up
									// the link to the external domain.
									$prefixLength = strlen($GLOBALS['TSFE']->config['config']['absRefPrefix']);
									if (substr($LD['totalURL'], 0, $prefixLength) == $GLOBALS['TSFE']->config['config']['absRefPrefix']) {
										$LD['totalURL'] = substr($LD['totalURL'], $prefixLength);
									}
								}
								$urlParts = parse_url($LD['totalURL']);
								if ($urlParts['host'] == '') {
									$LD['totalURL'] = $absoluteUrlScheme . '://' . $targetDomain . ($LD['totalURL']{0} == '/' ? '' : '/') . $LD['totalURL'];
								}
							}
							$this->lastTypoLinkUrl = $this->URLqMark($LD['totalURL'],'').$sectionMark;
						}

						$this->lastTypoLinkTarget = $LD['target'];
						$targetPart = $LD['target'] ? ' target="'.$LD['target'].'"' : '';

							// If sectionMark is set, there is no baseURL AND the current page is the page the link is to, check if there are any additional parameters or addQueryString parameters and if not, drop the url.
						if ($sectionMark && !$GLOBALS['TSFE']->config['config']['baseURL'] &&
								$page['uid'] == $GLOBALS['TSFE']->id && !trim($addQueryParams) &&
								!($conf['addQueryString'] && $conf['addQueryString.'])) {

							list(,$URLparams) = explode('?',$this->lastTypoLinkUrl);
							list($URLparams) = explode('#',$URLparams);
							parse_str ($URLparams.$LD['orig_type'], $URLparamsArray);
							if (intval($URLparamsArray['type'])==$GLOBALS['TSFE']->type)	{	// type nums must match as well as page ids
								unset($URLparamsArray['id']);
								unset($URLparamsArray['type']);
								if (!count($URLparamsArray))	{	// If there are no parameters left.... set the new url.
									$this->lastTypoLinkUrl = $sectionMark;
								}
							}
						}

							// If link is to a access restricted page which should be redirected, then find new URL:
						if ($GLOBALS['TSFE']->config['config']['typolinkLinkAccessRestrictedPages'] &&
								$GLOBALS['TSFE']->config['config']['typolinkLinkAccessRestrictedPages']!=='NONE' &&
								!$GLOBALS['TSFE']->checkPageGroupAccess($page))	{
									$thePage = $GLOBALS['TSFE']->sys_page->getPage($GLOBALS['TSFE']->config['config']['typolinkLinkAccessRestrictedPages']);

									$addParams = $GLOBALS['TSFE']->config['config']['typolinkLinkAccessRestrictedPages_addParams'];
									$addParams = str_replace('###RETURN_URL###',rawurlencode($this->lastTypoLinkUrl),$addParams);
									$addParams = str_replace('###PAGE_ID###',$page['uid'],$addParams);
									$this->lastTypoLinkUrl = $this->getTypoLink_URL(
										$thePage['uid'] . ($theTypeP ? ',' . $theTypeP : ''),
										$addParams,
										$target
									);
									$this->lastTypoLinkUrl = $this->forceAbsoluteUrl($this->lastTypoLinkUrl, $conf);
									$this->lastTypoLinkLD['totalUrl'] = $this->lastTypoLinkUrl;
									$LD = $this->lastTypoLinkLD;
						}

							// Rendering the tag.
						$finalTagParts['url']=$this->lastTypoLinkUrl;
						$finalTagParts['targetParams']=$targetPart;
						$finalTagParts['TYPE']='page';
					} else {
						$GLOBALS['TT']->setTSlogMessage("typolink(): Page id '".$link_param."' was not found, so '".$linktxt."' was not linked.",1);
						return $linktxt;
					}
				}
			}

			$this->lastTypoLinkLD = $LD;

			if ($forceTitle) {
				$title=$forceTitle;
			}

			if ($JSwindowParams) {

					// Create TARGET-attribute only if the right doctype is used
				if (!t3lib_div::inList('xhtml_strict,xhtml_11,xhtml_2', $GLOBALS['TSFE']->xhtmlDoctype))	{
					$target = ' target="FEopenLink"';
				} else {
					$target = '';
				}

				$onClick="vHWin=window.open('".$GLOBALS['TSFE']->baseUrlWrap($finalTagParts['url'])."','FEopenLink','".$JSwindowParams."');vHWin.focus();return false;";
				$res = '<a href="'.htmlspecialchars($finalTagParts['url']).'"'. $target .' onclick="'.htmlspecialchars($onClick).'"'.($title?' title="'.$title.'"':'').($linkClass?' class="'.$linkClass.'"':'').$finalTagParts['aTagParams'].'>';
			} else {
				if ($GLOBALS['TSFE']->spamProtectEmailAddresses === 'ascii' && $finalTagParts['TYPE'] === 'mailto') {
					$res = '<a href="'.$finalTagParts['url'].'"'.($title?' title="'.$title.'"':'').$finalTagParts['targetParams'].($linkClass?' class="'.$linkClass.'"':'').$finalTagParts['aTagParams'].'>';
				} else {
					$res = '<a href="'.htmlspecialchars($finalTagParts['url']).'"'.($title?' title="'.$title.'"':'').$finalTagParts['targetParams'].($linkClass?' class="'.$linkClass.'"':'').$finalTagParts['aTagParams'].'>';
				}
			}

				// Call user function:
			if ($conf['userFunc'])	{
				$finalTagParts['TAG']=$res;
				$res = $this->callUserFunction($conf['userFunc'],$conf['userFunc.'],$finalTagParts);
			}

				// Hook: Call post processing function for link rendering:
			if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typoLink_PostProc']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typoLink_PostProc'])) {
				$_params = array(
					'conf' => &$conf,
					'linktxt' => &$linktxt,
					'finalTag' => &$res,
					'finalTagParts' => &$finalTagParts,
				);
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typoLink_PostProc'] as $_funcRef) {
					t3lib_div::callUserFunction($_funcRef, $_params, $this);
				}
			}

				// If flag "returnLastTypoLinkUrl" set, then just return the latest URL made:
			if ($conf['returnLast'])	{
				switch($conf['returnLast'])	{
					case 'url':
						return $this->lastTypoLinkUrl;
					break;
					case 'target':
						return $this->lastTypoLinkTarget;
					break;
				}
			}

			if ($conf['ATagBeforeWrap'])	{
				return $res.$this->wrap($linktxt, $conf['wrap']).'</a>';
			} else {
				return $this->wrap($res.$linktxt.'</a>', $conf['wrap']);
			}
		} else {
			return $linktxt;
		}
	}

	/**
	 * Forces a given URL to be absolute.
	 *
	 * @param string $url The URL to be forced to be absolute
	 * @param array $configuration TypoScript configuration of typolink
	 * @return string The absolute URL
	 */
	protected function forceAbsoluteUrl($url, array $configuration) {
		if (!empty($url) && isset($configuration['forceAbsoluteUrl']) && $configuration['forceAbsoluteUrl']) {
			if (preg_match('#^(?:([a-z]+)(://))?([^/]*)(.*)$#', $url, $matches)) {
				$urlParts = array(
					'scheme' => $matches[1],
					'delimiter' => '://',
					'host' => $matches[3],
					'path' => $matches[4],
				);

				// Set scheme and host if not yet part of the URL:
				if (empty($urlParts['host'])) {
					$urlParts['scheme'] = 'http';
					$urlParts['host'] = t3lib_div::getIndpEnv('HTTP_HOST');
					$isUrlModified = TRUE;
				}

				// Override scheme:
				$forceAbsoluteUrl =& $configuration['forceAbsoluteUrl.']['scheme'];
				if (!empty($forceAbsoluteUrl) && $urlParts['scheme'] !== $forceAbsoluteUrl) {
					$urlParts['scheme'] = $forceAbsoluteUrl;
					$isUrlModified = TRUE;
				}

				// Recreate the absolute URL:
				if ($isUrlModified) {
					$url = implode('', $urlParts);
				}
			}
		}

		return $url;
	}

	/**
	 * Based on the input "TypoLink" TypoScript configuration this will return the generated URL
	 *
	 * @param	array		TypoScript properties for "typolink"
	 * @return	string		The URL of the link-tag that typolink() would by itself return
	 * @see typoLink()
	 */
	function typoLink_URL($conf)	{
		$this->typolink('|',$conf);
		return $this->lastTypoLinkUrl;
	}

	/**
	 * Returns a linked string made from typoLink parameters.
	 *
	 * This function takes $label as a string, wraps it in a link-tag based on the $params string, which should contain data like that you would normally pass to the popular <LINK>-tag in the TSFE.
	 * Optionally you can supply $urlParameters which is an array with key/value pairs that are rawurlencoded and appended to the resulting url.
	 *
	 * @param	string		Text string being wrapped by the link.
	 * @param	string		Link parameter; eg. "123" for page id, "kasperYYYY@typo3.com" for email address, "http://...." for URL, "fileadmin/blabla.txt" for file.
	 * @param	array		An array with key/value pairs representing URL parameters to set. Values NOT URL-encoded yet.
	 * @param	string		Specific target set, if any. (Default is using the current)
	 * @return	string		The wrapped $label-text string
	 * @see getTypoLink_URL()
	 */
	function getTypoLink($label,$params,$urlParameters=array(),$target='')	{
		$conf=array();
		$conf['parameter'] = $params;
		if ($target)	{
			$conf['target']=$target;
			$conf['extTarget']=$target;
			$conf['fileTarget']=$target;
		}
		if (is_array($urlParameters))	{
			if (count($urlParameters))	{
				$conf['additionalParams'].= t3lib_div::implodeArrayForUrl('',$urlParameters);
			}
		} else {
			$conf['additionalParams'].=$urlParameters;
		}
		$out = $this->typolink($label,$conf);
		return $out;
	}

	/**
	 * Returns the URL of a "typolink" create from the input parameter string, url-parameters and target
	 *
	 * @param	string		Link parameter; eg. "123" for page id, "kasperYYYY@typo3.com" for email address, "http://...." for URL, "fileadmin/blabla.txt" for file.
	 * @param	array		An array with key/value pairs representing URL parameters to set. Values NOT URL-encoded yet.
	 * @param	string		Specific target set, if any. (Default is using the current)
	 * @return	string		The URL
	 * @see getTypoLink()
	 */
	function getTypoLink_URL($params,$urlParameters=array(),$target='')	{
		$this->getTypoLink('',$params,$urlParameters,$target);
		return $this->lastTypoLinkUrl;
	}

	/**
	 * Generates a typolink and returns the two link tags - start and stop - in an array
	 *
	 * @param	array		"typolink" TypoScript properties
	 * @return	array		An array with two values in key 0+1, each value being the start and close <a>-tag of the typolink properties being inputted in $conf
	 * @see typolink()
	 */
	function typolinkWrap($conf)	{
		$k=md5(microtime());
		return explode($k,$this->typolink($k,$conf));
	}

	/**
	 * Returns the current page URL
	 *
	 * @param	array		Optionally you can specify additional URL parameters. An array with key/value pairs representing URL parameters to set. Values NOT URL-encoded yet.
	 * @param	integer		An alternative ID to the current id ($GLOBALS['TSFE']->id)
	 * @return	string		The URL
	 * @see getTypoLink_URL()
	 */
	function currentPageUrl($urlParameters=array(),$id=0)	{
		return $this->getTypoLink_URL($id?$id:$GLOBALS['TSFE']->id,$urlParameters,$GLOBALS['TSFE']->sPre);
	}

	/**
	 * Returns the &MP variable value for a page id.
	 * The function will do its best to find a MP value that will keep the page id inside the current Mount Point rootline if any.
	 *
	 * @param	integer		page id
	 * @param	boolean		If true, the MPvalue is returned raw. Normally it is encoded as &MP=... variable
	 * @return	string		MP value, prefixed with &MP= (depending on $raw)
	 * @see typolink()
	 */
	function getClosestMPvalueForPage($pageId, $raw=FALSE)	{
			// MointPoints:
		if ($GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids'] && $GLOBALS['TSFE']->MP)	{

			if (!strcmp($GLOBALS['TSFE']->id, $pageId))	{	// same page as current.
				$MP = $GLOBALS['TSFE']->MP;
			} else { // ... otherwise find closest meeting point:
				$tCR_rootline = $GLOBALS['TSFE']->sys_page->getRootLine($pageId, '', TRUE);	// Gets rootline of linked-to page
				$inverseTmplRootline = array_reverse($GLOBALS['TSFE']->tmpl->rootLine);

				$rl_mpArray = array();
				$startMPaccu = FALSE;

					// Traverse root line of link uid and inside of that the REAL root line of current position.
				foreach($tCR_rootline as $tCR_data)	{
					foreach($inverseTmplRootline as $rlKey => $invTmplRLRec)	{

							// Force accumulating when in overlay mode: Links to this page have to stay within the current branch
						if ($invTmplRLRec['_MOUNT_OL'] && ($tCR_data['uid']==$invTmplRLRec['uid']))	{
							$startMPaccu = TRUE;
						}

							// Accumulate MP data:
						if ($startMPaccu && $invTmplRLRec['_MP_PARAM'])	{
							$rl_mpArray[] = $invTmplRLRec['_MP_PARAM'];
						}

							// If two PIDs matches and this is NOT the site root, start accumulation of MP data (on the next level):
							// (The check for site root is done so links to branches outsite the site but sharing the site roots PID is NOT detected as within the branch!)
						if ($tCR_data['pid']==$invTmplRLRec['pid'] && count($inverseTmplRootline)!=$rlKey+1)	{
							$startMPaccu = TRUE;
						}
					}
					if ($startMPaccu)	break;	// Good enough...
				}

				if (count($rl_mpArray))	{
					$MP = implode(',', array_reverse($rl_mpArray));
				}
			}
		}

		return !$raw  ? ($MP ? '&MP='.rawurlencode($MP) : '') : $MP;
	}

	/**
	 * Creates a href attibute for given $mailAddress.
	 * The function uses spamProtectEmailAddresses and Jumpurl functionality for encoding the mailto statement.
	 * If spamProtectEmailAddresses is disabled, it'll just return a string like "mailto:user@example.tld".
	 *
	 * @param	string		Email address
	 * @param	string		Link text, default will be the email address.
	 * @param	string		Initial link parameters, only used if Jumpurl functionality is enabled. Example: ?id=5&type=0
	 * @return	string		Returns a numerical array with two elements: 1) $mailToUrl, string ready to be inserted into the href attribute of the <a> tag, b) $linktxt: The string between starting and ending <a> tag.
	 */
	function getMailTo($mailAddress,$linktxt,$initP='?') {
		if(!strcmp($linktxt,''))	{ $linktxt = $mailAddress; }

		$mailToUrl = 'mailto:'.$mailAddress;

		if (!$GLOBALS['TSFE']->config['config']['jumpurl_enable'] || $GLOBALS['TSFE']->config['config']['jumpurl_mailto_disable']) {
			if ($GLOBALS['TSFE']->spamProtectEmailAddresses) {
				if ($GLOBALS['TSFE']->spamProtectEmailAddresses === 'ascii')	{
					$mailToUrl = $GLOBALS['TSFE']->encryptEmail($mailToUrl);
				} else {
					$mailToUrl = "javascript:linkTo_UnCryptMailto('".$GLOBALS['TSFE']->encryptEmail($mailToUrl)."');";
				}
				if ($GLOBALS['TSFE']->config['config']['spamProtectEmailAddresses_atSubst']) {
					$atLabel = trim($GLOBALS['TSFE']->config['config']['spamProtectEmailAddresses_atSubst']);
				}
				$spamProtectedMailAddress = str_replace('@', ($atLabel ? $atLabel : '(at)'), $mailAddress);

				if ($GLOBALS['TSFE']->config['config']['spamProtectEmailAddresses_lastDotSubst']) {
					$lastDotLabel = trim($GLOBALS['TSFE']->config['config']['spamProtectEmailAddresses_lastDotSubst']);
					$lastDotLabel = $lastDotLabel ? $lastDotLabel : '(dot)';
					$spamProtectedMailAddress = preg_replace('/\.([^\.]+)$/', $lastDotLabel.'$1', $spamProtectedMailAddress);
				}
				$linktxt = str_ireplace($mailAddress, $spamProtectedMailAddress, $linktxt);
			}
		} else {
			$mailToUrl = $GLOBALS['TSFE']->absRefPrefix.$GLOBALS['TSFE']->config['mainScript'].$initP.'&jumpurl='.rawurlencode($mailToUrl).$GLOBALS['TSFE']->getMethodUrlIdToken;
		}
		return array($mailToUrl,$linktxt);
	}

	/**
	 * Gets the query arguments and assembles them for URLs.
	 * Arguments may be removed or set, depending on configuration.
	 *
	 * @param	string		Configuration
	 * @param	array		Multidimensional key/value pairs that overrule incoming query arguments
	 * @param	boolean		If set, key/value pairs not in the query but the overrule array will be set
	 * @return	string		The URL query part (starting with a &)
	 */
	public function getQueryArguments($conf, $overruleQueryArguments=array(), $forceOverruleArguments = FALSE) {
		switch ((string)$conf['method']) {
			case 'GET':
				$currentQueryArray = t3lib_div::_GET();
			break;
			case 'POST':
				$currentQueryArray = t3lib_div::_POST();
			break;
			case 'GET,POST':
				$currentQueryArray = array_merge(t3lib_div::_GET(), t3lib_div::_POST());
			break;
			case 'POST,GET':
				$currentQueryArray = array_merge(t3lib_div::_POST(), t3lib_div::_GET());
			break;
			default:
				$currentQueryArray = t3lib_div::explodeUrl2Array(t3lib_div::getIndpEnv('QUERY_STRING'), TRUE);
		}

		if ($conf['exclude']) {
			$exclude = str_replace(',', '&', $conf['exclude']);
			$exclude = t3lib_div::explodeUrl2Array($exclude, TRUE);
				// never repeat id
			$exclude['id'] = 0;
			$newQueryArray = t3lib_div::arrayDiffAssocRecursive($currentQueryArray, $exclude);
		} else {
			$newQueryArray = $currentQueryArray;
		}

		if ($forceOverruleArguments) {
			$newQueryArray = t3lib_div::array_merge_recursive_overrule($newQueryArray, $overruleQueryArguments);
		} else {
			$newQueryArray = t3lib_div::array_merge_recursive_overrule($newQueryArray, $overruleQueryArguments, TRUE);
		}

		return t3lib_div::implodeArrayForUrl('', $newQueryArray);
	}


















	/***********************************************
	 *
	 * Miscellaneous functions, stand alone
	 *
	 ***********************************************/

	/**
	 * Wrapping a string.
	 * Implements the TypoScript "wrap" property.
	 * Example: $content = "HELLO WORLD" and $wrap = "<strong> | </strong>", result: "<strong>HELLO WORLD</strong>"
	 *
	 * @param	string		The content to wrap
	 * @param	string		The wrap value, eg. "<strong> | </strong>"
	 * @param	string		The char used to split the wrapping value, default is "|"
	 * @return	string		Wrapped input string
	 * @see noTrimWrap()
	 */
	function wrap($content,$wrap,$char='|')	{
		
		if (!empty($wrap)) {
			if (($pos = strpos($wrap, $char)) === false) {
				throw new tx_lint_exception(sprintf('Char "%s" was not found in wrap "%s"', $char, $wrap));
			}
			if ((strpos($wrap, $char, $pos+1)) !== false) {
				throw new tx_lint_exception(sprintf('Found char "%s" more than once in wrap "%s"', $char, $wrap));
			}
		}
		
		return parent::wrap($content, $wrap, $char);
	}

	/**
	 * Wrapping a string, preserving whitespace in wrap value.
	 * Notice that the wrap value uses part 1/2 to wrap (and not 0/1 which wrap() does)
	 *
	 * @param	string		The content to wrap, eg. "HELLO WORLD"
	 * @param	string		The wrap value, eg. " | <strong> | </strong>"
	 * @return	string		Wrapped input string, eg. " <strong> HELLO WORD </strong>"
	 * @see wrap()
	 */
	function noTrimWrap($content,$wrap)	{
		
		if (preg_match('/^\|(.*)\|(.*)\|$/U', trim($wrap)) == 0) {
			throw new tx_lint_exception('noTrimWrap is not | val1 | val2 |');
		}
		
		return parent::noTrimWrap($content, $wrap);
	}

	/**
	 * Adds space above/below the input HTML string. It is done by adding a clear-gif and <br /> tag before and/or after the content.
	 *
	 * @param	string		The content to add space above/below to.
	 * @param	string		A value like "10 | 20" where the first part denotes the space BEFORE and the second part denotes the space AFTER (in pixels)
	 * @param	array		Configuration from TypoScript
	 * @return	string		Wrapped string
	 */
	function wrapSpace($content, $wrap, array $conf = NULL) {
		if (trim($wrap)) {
			$wrapArray = explode('|',$wrap);
			$wrapBefore = intval($wrapArray[0]);
			$wrapAfter = intval($wrapArray[1]);
			$useDivTag = (isset($conf['useDiv']) && $conf['useDiv']);
			if ($wrapBefore) {
				if($useDivTag) {
					$content = '<div class="content-spacer spacer-before" style="height:' . $wrapBefore . 'px;"></div>' . $content;
				} else {
					$content = '<img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="1" height="' . $wrapBefore . '"' . $this->getBorderAttr(' border="0"') . ' class="spacer-gif" alt="" title="" /><br />' . $content;
				}
			}
			if ($wrapAfter) {
				if($useDivTag) {
					$content.= '<div class="content-spacer spacer-after" style="height:' . $wrapAfter . 'px;"></div>';
				} else {
					$content.= '<img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="1" height="' . $wrapAfter . '"' . $this->getBorderAttr(' border="0"') . ' class="spacer-gif" alt="" title="" /><br />';
				}
			}
		}
		return $content;
	}

	/**
	 * Calling a user function/class-method
	 * Notice: For classes the instantiated object will have the internal variable, $cObj, set to be a *reference* to $this (the parent/calling object).
	 *
	 * @param	string		The functionname, eg "user_myfunction" or "user_myclass->main". Notice that there are rules for the names of functions/classes you can instantiate. If a function cannot be called for some reason it will be seen in the TypoScript log in the AdminPanel.
	 * @param	array		The TypoScript configuration to pass the function
	 * @param	string		The content string to pass the function
	 * @return	string		The return content from the function call. Should probably be a string.
	 * @see USER(), stdWrap(), typoLink(), _parseFunc()
	 */
	function callUserFunction($funcName,$conf,$content)	{
		$pre = $GLOBALS['TSFE']->TYPO3_CONF_VARS['FE']['userFuncClassPrefix'];
		if ($pre &&
			!t3lib_div::isFirstPartOfStr(trim($funcName),$pre) &&
			!t3lib_div::isFirstPartOfStr(trim($funcName),'tx_')
			)	{
			$GLOBALS['TT']->setTSlogMessage('Function "'.$funcName.'" was not prepended with "'.$pre.'"',3);
			return $content;
		}
			// Split parts
		$parts = explode('->',$funcName);
		if (count($parts)==2)	{	// Class
				// Check whether class is available and try to reload includeLibs if possible:
			if ($this->isClassAvailable($parts[0], $conf)) {
				$classObj = t3lib_div::makeInstance($parts[0]);
				if (is_object($classObj) && method_exists($classObj, $parts[1])) {
					$classObj->cObj = $this;
				 	$content = call_user_func_array(array($classObj, $parts[1]), array($content, $conf));
				} else {
					$GLOBALS['TT']->setTSlogMessage('Method "' . $parts[1] . '" did not exist in class "' . $parts[0] . '"', 3);
				}
			} else {
				$GLOBALS['TT']->setTSlogMessage('Class "' . $parts[0] . '" did not exist', 3);
			}
		} else {	// Function
			if (function_exists($funcName))	{
			 	$content = call_user_func($funcName, $content, $conf);
			} else {
				$GLOBALS['TT']->setTSlogMessage('Function "'.$funcName.'" did not exist',3);
			}
		}
		return $content;
	}

	/**
	 * Parses a set of text lines with "[parameters] = [values]" into an array with parameters as keys containing the value
	 * If lines are empty or begins with "/" or "#" then they are ignored.
	 *
	 * @param	string		Text which the parameters
	 * @return	array		Array with the parameters as key/value pairs
	 */
	function processParams($params)	{
		$paramArr=array();
		$lines=t3lib_div::trimExplode(LF,$params,1);
		foreach($lines as $val)	{
			$pair = explode('=',$val,2);
			if (!t3lib_div::inList('#,/',substr(trim($pair[0]),0,1)))	{
				$paramArr[trim($pair[0])] = trim($pair[1]);
			}
		}
		return $paramArr;
	}

	/**
	 * Cleans up a string of keywords. Keywords at splitted by "," (comma)  ";" (semi colon) and linebreak
	 *
	 * @param	string		String of keywords
	 * @return	string		Cleaned up string, keywords will be separated by a comma only.
	 */
	function keywords($content)	{
		$listArr = preg_split('/[,;' . LF . ']/', $content);
		foreach ($listArr as $k => $v) {
			$listArr[$k]=trim($v);
		}
		return implode(',',$listArr);
	}

	/**
	 * Changing character case of a string, converting typically used western charset characters as well.
	 *
	 * @param	string		The string to change case for.
	 * @param	string		The direction; either "upper" or "lower"
	 * @return	string
	 * @see HTMLcaseshift()
	 */
	function caseshift($theValue, $case)	{
		$case = strtolower($case);
		switch($case)	{
			case 'upper':
				$theValue = $GLOBALS['TSFE']->csConvObj->conv_case($GLOBALS['TSFE']->renderCharset,$theValue,'toUpper');
				#$theValue = strtoupper($theValue);
				#$theValue = strtr($theValue, $this->caseConvStrings[0], $this->caseConvStrings[1]);
			break;
			case 'lower':
				$theValue = $GLOBALS['TSFE']->csConvObj->conv_case($GLOBALS['TSFE']->renderCharset,$theValue,'toLower');
				#$theValue = strtolower($theValue);
				#$theValue = strtr($theValue, $this->caseConvStrings[1], $this->caseConvStrings[0]);
			break;
		}
		return $theValue;
	}

	/**
	 * Shifts the case of characters outside of HTML tags in the input string
	 *
	 * @param	string		The string to change case for.
	 * @param	string		The direction; either "upper" or "lower"
	 * @return	string
	 * @see caseshift()
	 */
	function HTMLcaseshift($theValue, $case)	{
		$inside = 0;
		$newVal = '';
		$pointer = 0;
		$totalLen = strlen($theValue);
		do	{
			if (!$inside)	{
				$len = strcspn(substr($theValue,$pointer),'<');
				$newVal.= $this->caseshift(substr($theValue,$pointer,$len),$case);
				$inside = 1;
			} else {
				$len = strcspn(substr($theValue,$pointer),'>')+1;
				$newVal.= substr($theValue,$pointer,$len);
				$inside = 0;
			}
			$pointer+=$len;
		} while($pointer<$totalLen);
		return $newVal;
	}

	/**
	 * Formats a number to GB, Mb or Kb or just bytes
	 *
	 * @param	integer		Number of bytes to format.
	 * @param	string		Labels for bytes, kilo, mega and giga separated by vertical bar (|) and possibly encapsulated in "". Eg: " | K| M| G" (which is the default value)
	 * @return	string
	 * @see t3lib_div::formatSize(), stdWrap()
	 * @deprecated since TYPO3 3.6 - Use t3lib_div::formatSize() instead
	 */
	function bytes($sizeInBytes,$labels)	{
		t3lib_div::logDeprecatedFunction();

		return t3lib_div::formatSize($sizeInBytes,$labels);
	}

	/**
	 * Returns the 'age' of the tstamp $seconds
	 *
	 * @param	integer		Seconds to return age for. Example: "70" => "1 min", "3601" => "1 hrs"
	 * @param	string		$labels are the labels of the individual units. Defaults to : ' min| hrs| days| yrs'
	 * @return	string		The formatted string
	 */
	function calcAge($seconds,$labels)	{
		if (t3lib_div::testInt($labels)) {
			$labels = ' min| hrs| days| yrs';
		} else {
			$labels=str_replace('"','',$labels);
		}

		$labelArr = explode('|',$labels);
		$absSeconds = abs($seconds);
		if ($absSeconds<3600)	{
			$seconds = round ($seconds/60).$labelArr[0];
		} elseif ($absSeconds<24*3600)	{
			$seconds = round ($seconds/3600).$labelArr[1];
		} elseif ($absSeconds<365*24*3600)	{
			$seconds = round ($seconds/(24*3600)).$labelArr[2];
		} else {
			$seconds = round ($seconds/(365*24*3600)).$labelArr[3];
		}
		return $seconds;
	}

	/**
	 * Sending a notification email using $GLOBALS['TSFE']->plainMailEncoded()
	 *
	 * @param	string		The message content. If blank, no email is sent.
	 * @param	string		Comma list of recipient email addresses
	 * @param	string		Email address of recipient of an extra mail. The same mail will be sent ONCE more; not using a CC header but sending twice.
	 * @param	string		"From" email address
	 * @param	string		Optional "From" name
	 * @param	string		Optional "Reply-To" header email address.
	 * @return	boolean		Returns true if sent
	 */
	function sendNotifyEmail($msg, $recipients, $cc, $email_from, $email_fromName='', $replyTo='')	{
			// Sends order emails:
		$headers=array();
		if ($email_from)	{$headers[]='From: '.$email_fromName.' <'.$email_from.'>';}
		if ($replyTo)		{$headers[]='Reply-To: '.$replyTo;}

		$recipients=implode(',',t3lib_div::trimExplode(',',$recipients,1));

		$emailContent = trim($msg);
		if ($emailContent)	{
			$parts = explode(LF, $emailContent, 2);		// First line is subject
			$subject=trim($parts[0]);
			$plain_message=trim($parts[1]);

			if ($recipients)	$GLOBALS['TSFE']->plainMailEncoded($recipients, $subject, $plain_message, implode(LF,$headers));
			if ($cc)	$GLOBALS['TSFE']->plainMailEncoded($cc, $subject, $plain_message, implode(LF,$headers));
			return true;
		}
	}

	/**
	 * Checks if $url has a '?' in it and if not, a '?' is inserted between $url and $params, which are anyway concatenated and returned
	 *
	 * @param	string		Input URL
	 * @param	string		URL parameters
	 * @return	string
	 */
	function URLqMark($url,$params)	{
		if ($params && !strstr($url,'?'))	{
			return $url.'?'.$params;
		} else {
			return $url.$params;
		}
	}

	/**
	 * Checking syntax of input email address
	 *
	 * @param	string		Input string to evaluate
	 * @return	boolean		Returns true if the $email address (input string) is valid; Has a "@", domain name with at least one period and only allowed a-z characters.
	 * @see t3lib_div::validEmail()
	 * @deprecated since TYPO3 3.6 - Use t3lib_div::validEmail() instead
	 */
	function checkEmail($email)	{
		t3lib_div::logDeprecatedFunction();

		return t3lib_div::validEmail($email);
	}

	/**
	 * Clears TypoScript properties listed in $propList from the input TypoScript array.
	 *
	 * @param	array		TypoScript array of values/properties
	 * @param	string		List of properties to clear both value/properties for. Eg. "myprop,another_property"
	 * @return	array		The TypoScript array
	 * @see gifBuilderTextBox()
	 */
	function clearTSProperties($TSArr,$propList)	{
		$list = explode(',',$propList);
		foreach ($list as $prop) {
			$prop = trim($prop);
			unset($TSArr[$prop]);
			unset($TSArr[$prop.'.']);
		}
		return $TSArr;
	}

	/**
	 * Resolves a TypoScript reference value to the full set of properties BUT overridden with any local properties set.
	 * So the reference is resolved but overlaid with local TypoScript properties of the reference value.
	 *
	 * @param	array		The TypoScript array
	 * @param	string		The property name: If this value is a reference (eg. " < plugins.tx_something") then the reference will be retrieved and inserted at that position (into the properties only, not the value...) AND overlaid with the old properties if any.
	 * @return	array		The modified TypoScript array
	 * @see user_plaintext::typolist(),user_plaintext::typohead()
	 */
	function mergeTSRef($confArr,$prop)	{
		if (substr($confArr[$prop],0,1)=='<')	{
			$key = trim(substr($confArr[$prop],1));
			$cF = t3lib_div::makeInstance('t3lib_TSparser');
				// $name and $conf is loaded with the referenced values.
			$old_conf=$confArr[$prop.'.'];
			list($name, $conf) = $cF->getVal($key,$GLOBALS['TSFE']->tmpl->setup);
			if (is_array($old_conf) && count($old_conf))	{
				$conf = $this->joinTSarrays($conf,$old_conf);
			}
			$confArr[$prop.'.']=$conf;
		}
		return $confArr;
	}

	/**
	 * Merges two TypoScript propery array, overlaing the $old_conf onto the $conf array
	 *
	 * @param	array		TypoScript property array, the "base"
	 * @param	array		TypoScript property array, the "overlay"
	 * @return	array		The resulting array
	 * @see mergeTSRef(), tx_tstemplatestyler_modfunc1::joinTSarrays()
	 */
	function joinTSarrays($conf,$old_conf)	{
		if (is_array($old_conf))	{
			foreach ($old_conf as $key => $val) {
				if (is_array($val))	{
					$conf[$key] = $this->joinTSarrays($conf[$key],$val);
				} else {
					$conf[$key] = $val;
				}
			}
		}
		return $conf;
	}

	/**
	 * This function creates a number of TEXT-objects in a Gifbuilder configuration in order to create a text-field like thing. Used with the script tslib/media/scripts/postit.inc
	 *
	 * @param	array		TypoScript properties for Gifbuilder - TEXT GIFBUILDER objects are added to this array and returned.
	 * @param	array		TypoScript properties for this function
	 * @param	string		The text string to write onto the GIFBUILDER file
	 * @return	array		The modified $gifbuilderConf array
	 * @see media/scripts/postit.inc
	 */
	function gifBuilderTextBox($gifbuilderConf, $conf, $text)	{
		$chars = intval($conf['chars']) ? intval($conf['chars']) : 20;
		$lineDist = intval($conf['lineDist']) ? intval($conf['lineDist']) : 20;
		$Valign = strtolower(trim($conf['Valign']));
		$tmplObjNumber = intval($conf['tmplObjNumber']);
		$maxLines = intval($conf['maxLines']);

		if ($tmplObjNumber && $gifbuilderConf[$tmplObjNumber]=='TEXT')	{
			$textArr = $this->linebreaks($text,$chars,$maxLines);
			$angle = intval($gifbuilderConf[$tmplObjNumber.'.']['angle']);
			foreach ($textArr as $c => $textChunk) {
				$index = $tmplObjNumber+1+($c*2);
					// Workarea
				$gifbuilderConf = $this->clearTSProperties($gifbuilderConf,$index);
				$rad_angle = 2*pi()/360*$angle;
				$x_d = sin($rad_angle)*$lineDist;
				$y_d = cos($rad_angle)*$lineDist;

				$diff_x_d=0;
				$diff_y_d=0;
				if ($Valign=='center')	{
					$diff_x_d = $x_d*count($textArr);
					$diff_x_d = $diff_x_d/2;
					$diff_y_d = $y_d*count($textArr);
					$diff_y_d = $diff_y_d/2;
				}


				$x_d = round($x_d*$c - $diff_x_d);
				$y_d = round($y_d*$c - $diff_y_d);

				$gifbuilderConf[$index] = 'WORKAREA';
				$gifbuilderConf[$index.'.']['set'] = $x_d.','.$y_d;
					// Text
				$index++;
				$gifbuilderConf = $this->clearTSProperties($gifbuilderConf,$index);
				$gifbuilderConf[$index] = 'TEXT';
				$gifbuilderConf[$index.'.'] = $this->clearTSProperties($gifbuilderConf[$tmplObjNumber.'.'],'text');
				$gifbuilderConf[$index.'.']['text'] = $textChunk;
			}
			$gifbuilderConf = $this->clearTSProperties($gifbuilderConf,$tmplObjNumber);
		}
		return $gifbuilderConf;
	}

	/**
	 * Splits a text string into lines and returns an array with these lines but a max number of lines.
	 *
	 * @param	string		The string to break
	 * @param	integer		Max number of characters per line.
	 * @param	integer		Max number of lines in all.
	 * @return	array		Array with lines.
	 * @access private
	 * @see gifBuilderTextBox()
	 */
	function linebreaks($string,$chars,$maxLines=0)	{
		$lines = explode(LF,$string);
		$lineArr=Array();
		$c=0;
		foreach ($lines as $paragraph) {
			$words = explode(' ',$paragraph);
			foreach ($words as $word) {
				if (strlen($lineArr[$c].$word)>$chars)	{
					$c++;
				}
				if (!$maxLines || $c<$maxLines)	{
					$lineArr[$c].= $word.' ';
				}
			}
			$c++;
		}
		return $lineArr;
	}

	/**
	 * Returns a JavaScript <script> section with some function calls to JavaScript functions from "t3lib/jsfunc.updateform.js" (which is also included by setting a reference in $GLOBALS['TSFE']->additionalHeaderData['JSincludeFormupdate'])
	 * The JavaScript codes simply transfers content into form fields of a form which is probably used for editing information by frontend users. Used by fe_adminLib.inc.
	 *
	 * @param	array		Data array which values to load into the form fields from $formName (only field names found in $fieldList)
	 * @param	string		The form name
	 * @param	string		A prefix for the data array
	 * @param	string		The list of fields which are loaded
	 * @return	string
	 * @access private
	 * @see user_feAdmin::displayCreateScreen()
	 */
	function getUpdateJS($dataArray, $formName, $arrPrefix, $fieldList)	{
		$JSPart='';
		$updateValues=t3lib_div::trimExplode(',',$fieldList);
		foreach ($updateValues as $fKey) {
			$value = $dataArray[$fKey];
			if (is_array($value))	{
				foreach ($value as $Nvalue) {
					$JSPart.="
	updateForm('".$formName."','".$arrPrefix."[".$fKey."][]',".t3lib_div::quoteJSvalue($Nvalue, true).");";
				}

			} else {
				$JSPart.="
	updateForm('".$formName."','".$arrPrefix."[".$fKey."]',".t3lib_div::quoteJSvalue($value, true).");";
			}
		}
		$JSPart='<script type="text/javascript">
	/*<![CDATA[*/ '.$JSPart.'
	/*]]>*/
</script>
';
		$GLOBALS['TSFE']->additionalHeaderData['JSincludeFormupdate'] =
			'<script type="text/javascript" src="'
			. t3lib_div::createVersionNumberedFilename($GLOBALS['TSFE']->absRefPrefix
				. 't3lib/jsfunc.updateform.js')
			. '"></script>';
		return $JSPart;
	}

	/**
	 * Includes resources if the config property 'includeLibs' is set.
	 *
	 * @param	array		$config: TypoScript configuration
	 * @return	boolean		Whether a configuration for including libs was found and processed
	 */
	protected function includeLibs(array $config) {
		$librariesIncluded = false;

		if (isset($config['includeLibs']) && $config['includeLibs']) {
			$libraries = t3lib_div::trimExplode(',', $config['includeLibs'], true);
			$GLOBALS['TSFE']->includeLibraries($libraries);
			$librariesIncluded = true;
		}

		return $librariesIncluded;
	}

	/**
	 * Checks whether a PHP class is available. If the check fails, the method tries to
	 * determine the correct includeLibs to make the class available automatically.
	 *
	 * TypoScript example that can cause this:
	 * | plugin.tx_myext_pi1 = USER
	 * | plugin.tx_myext_pi1 {
	 * |   includeLibs = EXT:myext/pi1/class.tx_myext_pi1.php
	 * |   userFunc = tx_myext_pi1->main
	 * | }
	 * | 10 = USER
	 * | 10.userFunc = tx_myext_pi1->renderHeader
	 *
	 * @param	string		$className: The name of the PHP class to be checked
	 * @param	array		$config: TypoScript configuration (naturally of a USER or COA cObject)
	 * @return	boolean		Whether the class is available
	 * @link	http://bugs.typo3.org/view.php?id=9654
	 * @TODO	This method was introduced in TYPO3 4.3 and can be removed if the autoload was integrated
	 */
	protected function isClassAvailable($className, array $config = NULL) {
		if (class_exists($className)) {
			return true;
		} elseif ($config) {
			$pluginConfiguration =& $GLOBALS['TSFE']->tmpl->setup['plugin.'][$className . '.'];
			if (isset($pluginConfiguration['includeLibs']) && $pluginConfiguration['includeLibs']) {
				$config['includeLibs'] = $pluginConfiguration['includeLibs'];
				return $this->includeLibs($config);
			}
		}
		return false;
	}




























	/***********************************************
	 *
	 * Database functions, making of queries
	 *
	 ***********************************************/

	/**
	 * Returns an UPDATE/DELETE sql query which will "delete" the record.
	 * If the $TCA config for the table tells us to NOT "physically" delete the record but rather set the "deleted" field to "1" then an UPDATE query is returned doing just that. Otherwise it truely is a DELETE query.
	 *
	 * @param	string		The table name, should be in $TCA
	 * @param	integer		The UID of the record from $table which we are going to delete
	 * @param	boolean		If set, the query is executed. IT'S HIGHLY RECOMMENDED TO USE THIS FLAG to execute the query directly!!!
	 * @return	string		The query, ready to execute unless $doExec was true in which case the return value is false.
	 * @see DBgetUpdate(), DBgetInsert(), user_feAdmin
	 */
	function DBgetDelete($table, $uid, $doExec=FALSE)	{
		if (intval($uid))	{
			if ($GLOBALS['TCA'][$table]['ctrl']['delete'])	{
				if ($doExec)	{
					return $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid='.intval($uid), array($GLOBALS['TCA'][$table]['ctrl']['delete'] => 1));
				} else {
					return $GLOBALS['TYPO3_DB']->UPDATEquery($table, 'uid='.intval($uid), array($GLOBALS['TCA'][$table]['ctrl']['delete'] => 1));
				}
			} else {
				if ($doExec)	{
					return $GLOBALS['TYPO3_DB']->exec_DELETEquery($table, 'uid='.intval($uid));
				} else {
					return $GLOBALS['TYPO3_DB']->DELETEquery($table, 'uid='.intval($uid));
				}
			}
		}
	}

	/**
	 * Returns an UPDATE sql query.
	 * If a "tstamp" field is configured for the $table tablename in $TCA then that field is automatically updated to the current time.
	 * Notice: It is YOUR responsibility to make sure the data being updated is valid according the tablefield types etc. Also no logging is performed of the update. It's just a nice general usage API function for creating a quick query.
	 * NOTICE: From TYPO3 3.6.0 this function ALWAYS adds slashes to values inserted in the query.
	 *
	 * @param	string		The table name, should be in $TCA
	 * @param	integer		The UID of the record from $table which we are going to update
	 * @param	array		The data array where key/value pairs are fieldnames/values for the record to update.
	 * @param	string		Comma list of fieldnames which are allowed to be updated. Only values from the data record for fields in this list will be updated!!
	 * @param	boolean		If set, the query is executed. IT'S HIGHLY RECOMMENDED TO USE THIS FLAG to execute the query directly!!!
	 * @return	string		The query, ready to execute unless $doExec was true in which case the return value is false.
	 * @see DBgetInsert(), DBgetDelete(), user_feAdmin
	 */
	function DBgetUpdate($table, $uid, $dataArr, $fieldList, $doExec=FALSE)	{
		unset($dataArr['uid']);	// uid can never be set
		$uid=intval($uid);

		if ($uid)	{
			$fieldList = implode(',',t3lib_div::trimExplode(',',$fieldList,1));
			$updateFields=array();

			foreach($dataArr as $f => $v)	{
				if (t3lib_div::inList($fieldList,$f))	{
					$updateFields[$f] = $v;
				}
			}

			if ($GLOBALS['TCA'][$table]['ctrl']['tstamp'])	{
				$updateFields[$GLOBALS['TCA'][$table]['ctrl']['tstamp']] = $GLOBALS['EXEC_TIME'];
			}

			if (count($updateFields))	{
				if ($doExec)	{
					return $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid='.intval($uid), $updateFields);
				} else {
					return $GLOBALS['TYPO3_DB']->UPDATEquery($table, 'uid='.intval($uid), $updateFields);
				}
			}
		}
	}

	/**
	 * Returns an INSERT sql query which automatically added "system-fields" according to $TCA
	 * Automatically fields for "tstamp", "crdate", "cruser_id", "fe_cruser_id" and "fe_crgroup_id" is updated if they are configured in the "ctrl" part of $TCA.
	 * The "pid" field is overridden by the input $pid value if >= 0 (zero). "uid" can never be set as a field
	 * NOTICE: From TYPO3 3.6.0 this function ALWAYS adds slashes to values inserted in the query.
	 *
	 * @param	string		The table name, should be in $TCA
	 * @param	integer		The PID value for the record to insert
	 * @param	array		The data array where key/value pairs are fieldnames/values for the record to insert
	 * @param	string		Comma list of fieldnames which are allowed to be inserted. Only values from the data record for fields in this list will be inserted!!
	 * @param	boolean		If set, the query is executed. IT'S HIGHLY RECOMMENDED TO USE THIS FLAG to execute the query directly!!!
	 * @return	string		The query, ready to execute unless $doExec was true in which case the return value is false.
	 * @see DBgetUpdate(), DBgetDelete(), user_feAdmin
	 */
	function DBgetInsert($table, $pid, $dataArr, $fieldList, $doExec=FALSE)	{
		$extraList='pid';
		if ($GLOBALS['TCA'][$table]['ctrl']['tstamp']) {
			$field = $GLOBALS['TCA'][$table]['ctrl']['tstamp'];
			$dataArr[$field] = $GLOBALS['EXEC_TIME'];
			$extraList .= ',' . $field;
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['crdate']) {
			$field=$GLOBALS['TCA'][$table]['ctrl']['crdate'];
			$dataArr[$field] = $GLOBALS['EXEC_TIME'];
			$extraList .= ',' . $field;
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['cruser_id'])	{$field=$GLOBALS['TCA'][$table]['ctrl']['cruser_id']; $dataArr[$field]=0; $extraList.=','.$field;}
		if ($GLOBALS['TCA'][$table]['ctrl']['fe_cruser_id'])	{$field=$GLOBALS['TCA'][$table]['ctrl']['fe_cruser_id']; $dataArr[$field]=intval($GLOBALS['TSFE']->fe_user->user['uid']); $extraList.=','.$field;}
		if ($GLOBALS['TCA'][$table]['ctrl']['fe_crgroup_id'])	{$field=$GLOBALS['TCA'][$table]['ctrl']['fe_crgroup_id']; list($dataArr[$field])=explode(',',$GLOBALS['TSFE']->fe_user->user['usergroup']); $dataArr[$field]=intval($dataArr[$field]); $extraList.=','.$field;}

		unset($dataArr['uid']);	// uid can never be set
		if ($pid>=0)	{ $dataArr['pid'] = $pid; }		// Set pid < 0 and the dataarr-pid will be used!
		$fieldList = implode(',',t3lib_div::trimExplode(',',$fieldList.','.$extraList,1));

		$insertFields = array();
		foreach($dataArr as $f => $v)	{
			if (t3lib_div::inList($fieldList,$f))	{
				$insertFields[$f] = $v;
			}
		}

		if ($doExec)	{
			return $GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $insertFields);
		} else {
			return $GLOBALS['TYPO3_DB']->INSERTquery($table, $insertFields);
		}
	}

	/**
	 * Checks if a frontend user is allowed to edit a certain record
	 *
	 * @param	string		The table name, found in $TCA
	 * @param	array		The record data array for the record in question
	 * @param	array		The array of the fe_user which is evaluated, typ. $GLOBALS['TSFE']->fe_user->user
	 * @param	string		Commalist of the only fe_groups uids which may edit the record. If not set, then the usergroup field of the fe_user is used.
	 * @param	boolean		True, if the fe_user may edit his own fe_user record.
	 * @return	boolean
	 * @see user_feAdmin
	 */
	function DBmayFEUserEdit($table,$row, $feUserRow, $allowedGroups='',$feEditSelf=0)	{
		$groupList = $allowedGroups ? implode(',',array_intersect(t3lib_div::trimExplode(',',$feUserRow['usergroup'],1),t3lib_div::trimExplode(',',$allowedGroups,1))) : $feUserRow['usergroup'];
		$ok=0;
			// points to the field that allows further editing from frontend if not set. If set the record is locked.
		if (!$GLOBALS['TCA'][$table]['ctrl']['fe_admin_lock'] || !$row[$GLOBALS['TCA'][$table]['ctrl']['fe_admin_lock']])	{
				// points to the field (integer) that holds the fe_users-id of the creator fe_user
			if ($GLOBALS['TCA'][$table]['ctrl']['fe_cruser_id'])	{
				$rowFEUser = intval($row[$GLOBALS['TCA'][$table]['ctrl']['fe_cruser_id']]);
				if ($rowFEUser && $rowFEUser==$feUserRow['uid'])	{
					$ok=1;
				}
			}
				// If $feEditSelf is set, fe_users may always edit them selves...
			if ($feEditSelf && $table=='fe_users' && !strcmp($feUserRow['uid'],$row['uid']))	{
				$ok=1;
			}
				// points to the field (integer) that holds the fe_group-id of the creator fe_user's first group
			if ($GLOBALS['TCA'][$table]['ctrl']['fe_crgroup_id'])	{
				$rowFEUser = intval($row[$GLOBALS['TCA'][$table]['ctrl']['fe_crgroup_id']]);
				if ($rowFEUser)	{
					if (t3lib_div::inList($groupList, $rowFEUser))	{
						$ok=1;
					}
				}
			}
		}
		return $ok;
	}

	/**
	 * Returns part of a where clause for selecting records from the input table name which the user may edit.
	 * Conceptually close to the function DBmayFEUserEdit(); It does the same thing but not for a single record, rather for a select query selecting all records which the user HAS access to.
	 *
	 * @param	string		The table name
	 * @param	array		The array of the fe_user which is evaluated, typ. $GLOBALS['TSFE']->fe_user->user
	 * @param	string		Commalist of the only fe_groups uids which may edit the record. If not set, then the usergroup field of the fe_user is used.
	 * @param	boolean		True, if the fe_user may edit his own fe_user record.
	 * @return	string		The where clause part. ALWAYS returns a string. If no access at all, then " AND 1=0"
	 * @see DBmayFEUserEdit(), user_feAdmin::displayEditScreen()
	 */
	function DBmayFEUserEditSelect($table,$feUserRow,$allowedGroups='',$feEditSelf=0)	{
			// Returns where-definition that selects user-editable records.
		$groupList = $allowedGroups ? implode(',',array_intersect(t3lib_div::trimExplode(',',$feUserRow['usergroup'],1),t3lib_div::trimExplode(',',$allowedGroups,1))) : $feUserRow['usergroup'];
		$OR_arr=array();
			// points to the field (integer) that holds the fe_users-id of the creator fe_user
		if ($GLOBALS['TCA'][$table]['ctrl']['fe_cruser_id'])	{
			$OR_arr[]=$GLOBALS['TCA'][$table]['ctrl']['fe_cruser_id'].'='.$feUserRow['uid'];
		}
			// points to the field (integer) that holds the fe_group-id of the creator fe_user's first group
		if ($GLOBALS['TCA'][$table]['ctrl']['fe_crgroup_id'])	{
			$values = t3lib_div::intExplode(',',$groupList);
			foreach ($values as $theGroupUid) {
				if ($theGroupUid)	{$OR_arr[]=$GLOBALS['TCA'][$table]['ctrl']['fe_crgroup_id'].'='.$theGroupUid;}
			}
		}
			// If $feEditSelf is set, fe_users may always edit them selves...
		if ($feEditSelf && $table=='fe_users')	{
			$OR_arr[]='uid='.intval($feUserRow['uid']);
		}

		$whereDef=' AND 1=0';
		if (count($OR_arr))	{
			$whereDef=' AND ('.implode(' OR ',$OR_arr).')';
			if ($GLOBALS['TCA'][$table]['ctrl']['fe_admin_lock'])	{
				$whereDef.=' AND '.$GLOBALS['TCA'][$table]['ctrl']['fe_admin_lock'].'=0';
			}
		}
		return $whereDef;
	}

	/**
	 * Returns a part of a WHERE clause which will filter out records with start/end times or hidden/fe_groups fields set to values that should de-select them according to the current time, preview settings or user login. Definitely a frontend function.
	 * THIS IS A VERY IMPORTANT FUNCTION: Basically you must add the output from this function for EVERY select query you create for selecting records of tables in your own applications - thus they will always be filtered according to the "enablefields" configured in TCA
	 * Simply calls t3lib_pageSelect::enableFields() BUT will send the show_hidden flag along! This means this function will work in conjunction with the preview facilities of the frontend engine/Admin Panel.
	 *
	 * @param	string		The table for which to get the where clause
	 * @param	boolean		If set, then you want NOT to filter out hidden records. Otherwise hidden record are filtered based on the current preview settings.
	 * @return	string		The part of the where clause on the form " AND [fieldname]=0 AND ...". Eg. " AND hidden=0 AND starttime < 123345567"
	 * @see t3lib_pageSelect::enableFields()
	 */
	function enableFields($table,$show_hidden=0)	{
		return $GLOBALS['TSFE']->sys_page->enableFields($table,$show_hidden?$show_hidden:($table=='pages' ? $GLOBALS['TSFE']->showHiddenPage : $GLOBALS['TSFE']->showHiddenRecords));
	}

	/**
	 * Generates a list of Page-uid's from $id. List does not include $id itself
	 * (unless the id specified is negative in which case it does!)
	 * The only pages WHICH PREVENTS DECENDING in a branch are
	 *    - deleted pages,
	 *    - pages in a recycler (doktype = 255) or of the Backend User Section (doktpe = 6) type
	 *    - pages that has the extendToSubpages set, WHERE start/endtime, hidden
	 * 		and fe_users would hide the records.
	 * Apart from that, pages with enable-fields excluding them, will also be
	 * removed. HOWEVER $dontCheckEnableFields set will allow
	 * enableFields-excluded pages to be included anyway - including
	 * extendToSubpages sections!
	 * Mount Pages are also descended but notice that these ID numbers are not
	 * useful for links unless the correct MPvar is set.
	 *
	 * @param	integer		The id of the start page from which point in the page tree to decend. IF NEGATIVE the id itself is included in the end of the list (only if $begin is 0) AND the output does NOT contain a last comma. Recommended since it will resolve the input ID for mount pages correctly and also check if the start ID actually exists!
	 * @param	integer		The number of levels to decend. If you want to decend infinitely, just set this to 100 or so. Should be at least "1" since zero will just make the function return (no decend...)
	 * @param	integer		$begin is an optional integer that determines at which level in the tree to start collecting uid's. Zero means 'start right away', 1 = 'next level and out'
	 * @param	boolean		See function description
	 * @param	string		Additional fields to select. Syntax: ",[fieldname],[fieldname],..."
	 * @param	string		Additional where clauses. Syntax: " AND [fieldname]=[value] AND ..."
	 * @param	array		Array of IDs from previous recursions. In order to prevent infinite loops with mount pages.
	 * @param	integer		Internal: Zero for the first recursion, incremented for each recursive call.
	 * @return	string		Returns the list with a comma in the end (if any pages selected and not if $id is negative and $id is added itself) - which means the input page id can comfortably be appended to the output string if you need it to.
	 * @see tslib_fe::checkEnableFields(), tslib_fe::checkPagerecordForIncludeSection()
	 */
	public function getTreeList($id, $depth, $begin = 0, $dontCheckEnableFields = false, $addSelectFields = '', $moreWhereClauses = '', array $prevId_array = array(), $recursionLevel = 0)	{

			// Init vars:
		$allFields   = 'uid,hidden,starttime,endtime,fe_group,extendToSubpages,doktype,php_tree_stop,mount_pid,mount_pid_ol,t3ver_state'.$addSelectFields;
		$depth       = intval($depth);
		$begin       = intval($begin);
		$id          = intval($id);
		$theList     = '';
		$addId       = 0;
		$requestHash = '';

		if ($id) {

				// First level, check id (second level, this is done BEFORE the recursive call)
			if (!$recursionLevel) {

					// check tree list cache

					// first, create the hash for this request - not sure yet whether we need all these parameters though
				$parameters = array(
					$id,
					$depth,
					$begin,
					$dontCheckEnableFields,
					$addSelectFields,
					$moreWhereClauses,
					$prevId_array,
					$GLOBALS['TSFE']->gr_list
				);
				$requestHash = md5(serialize($parameters));

				list($cacheEntry) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'treelist',
					'cache_treelist',
					'md5hash = \'' . $requestHash . '\' AND ( expires > ' . $GLOBALS['EXEC_TIME'] . ' OR expires = 0 )'
				);

				if (is_array($cacheEntry)) {
						// cache hit
					return $cacheEntry['treelist'];
				}

					// If Id less than zero it means we should add the real id to list:
				if ($id < 0) {
					$addId = $id = abs($id);
				}
					// Check start page:
				if ($GLOBALS['TSFE']->sys_page->getRawRecord('pages', $id, 'uid')) {

						// Find mount point if any:
					$mount_info = $GLOBALS['TSFE']->sys_page->getMountPointInfo($id);
					if (is_array($mount_info)) {
						$id = $mount_info['mount_pid'];
							// In Overlay mode, use the mounted page uid as added ID!:
						if ($addId && $mount_info['overlay']) {
							$addId = $id;
						}
					}
				} else {
					return '';	// Return blank if the start page was NOT found at all!
				}
			}

				// Add this ID to the array of IDs
			if ($begin <= 0) {
				$prevId_array[] = $id;
			}

				// Select sublevel:
			if ($depth > 0) {
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					$allFields,
					'pages',
					'pid = '.intval($id).' AND deleted = 0 '.$moreWhereClauses,
					'',
					'sorting'
				);

				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$GLOBALS['TSFE']->sys_page->versionOL('pages', $row);

					if ($row['doktype'] == 255 || $row['doktype'] == 6 || $row['t3ver_state'] > 0) {
							// Doing this after the overlay to make sure changes
							// in the overlay are respected.
							// However, we do not process pages below of and
							// including of type recycler and BE user section
						continue;
					}

						// Find mount point if any:
					$next_id    = $row['uid'];
					$mount_info = $GLOBALS['TSFE']->sys_page->getMountPointInfo($next_id, $row);

						// Overlay mode:
					if (is_array($mount_info) && $mount_info['overlay']) {
						$next_id = $mount_info['mount_pid'];

						$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							$allFields,
							'pages',
							'uid = '.intval($next_id).' AND deleted = 0 '.$moreWhereClauses,
							'' ,
							'sorting'
						);
						$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res2);
						$GLOBALS['TYPO3_DB']->sql_free_result($res2);

						$GLOBALS['TSFE']->sys_page->versionOL('pages', $row);

						if ($row['doktype'] == 255 || $row['doktype'] == 6 || $row['t3ver_state'] > 0) {
								// Doing this after the overlay to make sure
								// changes in the overlay are respected.

								// see above
							continue;
						}
					}
						// Add record:
					if ($dontCheckEnableFields || $GLOBALS['TSFE']->checkPagerecordForIncludeSection($row)) {
							// Add ID to list:
						if ($begin <= 0) {
							if ($dontCheckEnableFields || $GLOBALS['TSFE']->checkEnableFields($row)) {
								$theList.= $next_id.',';
							}
						}
							// Next level:
						if ($depth > 1 && !$row['php_tree_stop']) {
								// Normal mode:
							if (is_array($mount_info) && !$mount_info['overlay']) {
								$next_id = $mount_info['mount_pid'];
							}
								// Call recursively, if the id is not in prevID_array:
							if (!in_array($next_id, $prevId_array)) {
								$theList.= tslib_cObj::getTreeList($next_id, $depth-1, $begin-1, $dontCheckEnableFields, $addSelectFields, $moreWhereClauses, $prevId_array, $recursionLevel+1);
							}
						}
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}

				// If first run, check if the ID should be returned:
			if (!$recursionLevel) {
				if ($addId) {
					if ($begin > 0) {
						$theList.= 0;
					} else {
						$theList.= $addId;
					}
				}

				$GLOBALS['TYPO3_DB']->exec_INSERTquery(
					'cache_treelist',
					array(
						'md5hash'  => $requestHash,
						'pid'      => $id,
						'treelist' => $theList,
						'tstamp'   => $GLOBALS['EXEC_TIME'],
					)
				);
			}
		}
			// Return list:
		return $theList;
	}

	/**
	 * Returns a part for a WHERE clause (without preceeding operator) which will select records based on the presence of a certain string in a string-list inside the record.
	 * Example: If you have a record with a field, "usergroup" and that field might contain a list like "1,2,3" (with no spaces between the values) then you can select all records having eg. "2" in this list by calling this function. This is regardless of whether the number "2" is in the start, end or middle of the list - or the only value at all.
	 *
	 * @param	string		The field name to look in
	 * @param	string		The value to look for.
	 * @return	string
	 * @deprecated since TYPO3 3.6 - Use $GLOBALS['TYPO3_DB']->listQuery() directly!
	 */
	function whereSelectFromList($field,$value)	{
		return $GLOBALS['TYPO3_DB']->listQuery($field,$value,'');
	}

	/**
	 * Executes a SELECT query for joining three tables according to the MM-relation standards used for tables configured in $TCA. That means MM-joins where the join table has the fields "uid_local" and "uid_foreign"
	 *
	 * @param	string		List of fields to select
	 * @param	string		The local table
	 * @param	string		The join-table; The "uid_local" field of this table will be matched with $local_table's "uid" field.
	 * @param	string		Optionally: The foreign table; The "uid" field of this table will be matched with $mm_table's "uid_foreign" field. If you set this field to blank the join will be over only the $local_table and $mm_table
	 * @param	string		Optional additional WHERE clauses put in the end of the query. DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
	 * @param	string		Optional GROUP BY field(s), if none, supply blank string.
	 * @param	string		Optional ORDER BY field(s), if none, supply blank string.
	 * @param	string		Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @return	pointer		SQL result pointer
	 * @see mm_query_uidList()
	 */
	function exec_mm_query($select,$local_table,$mm_table,$foreign_table,$whereClause='',$groupBy='',$orderBy='',$limit='')	{
		return $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					$select,
					$local_table.','.$mm_table.($foreign_table?','.$foreign_table:''),
					$local_table.'.uid='.$mm_table.'.uid_local'.($foreign_table?' AND '.$foreign_table.'.uid='.$mm_table.'.uid_foreign':'').
						$whereClause,	// whereClauseMightContainGroupOrderBy
					$groupBy,
					$orderBy,
					$limit
				);
	}

	/**
	 * Executes a SELECT query for joining two tables according to the MM-relation standards used for tables configured in $TCA. That means MM-joins where the join table has the fields "uid_local" and "uid_foreign"
	 * The two tables joined is the join table ($mm_table) and the foreign table ($foreign_table) - so the "local table" is not included but instead you can supply a list of UID integers from the local table to match in the join-table.
	 *
	 * @param	string		List of fields to select
	 * @param	string		List of UID integers, eg. "1,2,3,456"
	 * @param	string		The join-table; The "uid_local" field of this table will be matched with the list of UID numbers from $local_table_uidlist
	 * @param	string		Optionally: The foreign table; The "uid" field of this table will be matched with $mm_table's "uid_foreign" field. If you set this field to blank only records from the $mm_table is returned. No join performed.
	 * @param	string		Optional additional WHERE clauses put in the end of the query. DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
	 * @param	string		Optional GROUP BY field(s), if none, supply blank string.
	 * @param	string		Optional ORDER BY field(s), if none, supply blank string.
	 * @param	string		Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @return	pointer		SQL result pointer
	 * @see mm_query()
	 */
	function exec_mm_query_uidList($select,$local_table_uidlist,$mm_table,$foreign_table='',$whereClause='',$groupBy='',$orderBy='',$limit='')	{
		return $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					$select,
					$mm_table.($foreign_table?','.$foreign_table:''),
					$mm_table.'.uid_local IN ('.$local_table_uidlist.')'.($foreign_table?' AND '.$foreign_table.'.uid='.$mm_table.'.uid_foreign':'').
						$whereClause,	// whereClauseMightContainGroupOrderBy
					$groupBy,
					$orderBy,
					$limit
				);
	}

	/**
	 * Generates a search where clause based on the input search words (AND operation - all search words must be found in record.)
	 * Example: The $sw is "content management, system" (from an input form) and the $searchFieldList is "bodytext,header" then the output will be ' AND (bodytext LIKE "%content%" OR header LIKE "%content%") AND (bodytext LIKE "%management%" OR header LIKE "%management%") AND (bodytext LIKE "%system%" OR header LIKE "%system%")'
	 *
	 * @param	string		The search words. These will be separated by space and comma.
	 * @param	string		The fields to search in
	 * @param	string		The table name you search in (recommended for DBAL compliance. Will be prepended field names as well)
	 * @return	string		The WHERE clause.
	 */
	function searchWhere($sw,$searchFieldList,$searchTable='')	{
		global $TYPO3_DB;

		$prefixTableName = $searchTable ? $searchTable.'.' : '';
		$where = '';
		if ($sw)	{
			$searchFields = explode(',',$searchFieldList);
			$kw = preg_split('/[ ,]/', $sw);

			foreach ($kw as $val) {
				$val = trim($val);
				$where_p = array();
				if (strlen($val)>=2)	{
					$val = $TYPO3_DB->escapeStrForLike($TYPO3_DB->quoteStr($val,$searchTable),$searchTable);
					foreach ($searchFields as $field) {
						$where_p[] = $prefixTableName.$field.' LIKE \'%'.$val.'%\'';
					}
				}
				if (count($where_p))	{
					$where.=' AND ('.implode(' OR ',$where_p).')';
				}
			}
		}
		return $where;
	}

	/**
	 * Executes a SELECT query for records from $table and with conditions based on the configuration in the $conf array
	 * This function is preferred over ->getQuery() if you just need to create and then execute a query.
	 *
	 * @param	string		The table name
	 * @param	array		The TypoScript configuration properties
	 * @return	mixed		A SQL result pointer
	 * @see getQuery()
	 */
	function exec_getQuery($table, $conf)	{
		$queryParts = $this->getQuery($table, $conf, TRUE);

		return $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
	}

	/**
	 * Creates and returns a SELECT query for records from $table and with conditions based on the configuration in the $conf array
	 * Implements the "select" function in TypoScript
	 *
	 * @param	string		See ->exec_getQuery()
	 * @param	array		See ->exec_getQuery()
	 * @param	boolean		If set, the function will return the query not as a string but array with the various parts. RECOMMENDED!
	 * @return	mixed		A SELECT query if $returnQueryArray is false, otherwise the SELECT query in an array as parts.
	 * @access private
	 * @see CONTENT(), numRows()
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=318&cHash=a98cb4e7e6
	 */
	function getQuery($table, $conf, $returnQueryArray=FALSE)	{

			// Handle PDO-style named parameter markers first
		$queryMarkers = $this->getQueryMarkers($table, $conf);

			// replace the markers in the non-stdWrap properties
		foreach ($queryMarkers as $marker => $markerValue) {
			$properties = array('uidInList', 'selectFields', 'where', 'max',
				'begin', 'groupBy', 'orderBy', 'join', 'leftjoin', 'rightjoin');
			foreach ($properties as $property) {
				if ($conf[$property]) {
					$conf[$property] = str_replace('###' . $marker . '###',
							$markerValue,
							$conf[$property]);
				}
			}
		}

			// Construct WHERE clause:
		$conf['pidInList'] = trim($this->stdWrap($conf['pidInList'],$conf['pidInList.']));

		// Handle recursive function for the pidInList
		if (isset($conf['recursive'])) {
			$conf['recursive'] = intval($conf['recursive']);
			if ($conf['recursive'] > 0) {
				foreach (explode(',', $conf['pidInList']) as $value) {
					if ($value === 'this') {
						$value = $GLOBALS['TSFE']->id;
					}
					$pidList .= $value . ',' . $this->getTreeList($value, $conf['recursive']);
				}
				$conf['pidInList'] = trim($pidList, ',');
			}
		}

		if (!strcmp($conf['pidInList'],''))	{
			$conf['pidInList'] = 'this';
		}
		$queryParts = $this->getWhere($table,$conf,TRUE);

			// Fields:
		$queryParts['SELECT'] = $conf['selectFields'] ? $conf['selectFields'] : '*';

			// Setting LIMIT:
		if ($conf['max'] || $conf['begin']) {
			$error=0;

				// Finding the total number of records, if used:
			if (strstr(strtolower($conf['begin'].$conf['max']),'total'))	{
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)', $table, $queryParts['WHERE'], $queryParts['GROUPBY']);
				if ($error = $GLOBALS['TYPO3_DB']->sql_error())	{
					$GLOBALS['TT']->setTSlogMessage($error);
				} else {
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
					$conf['max'] = str_ireplace('total', $row[0], $conf['max']);
					$conf['begin'] = str_ireplace('total', $row[0], $conf['begin']);
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
			if (!$error)	{
				$conf['begin'] = t3lib_div::intInRange(ceil($this->calc($conf['begin'])),0);
				$conf['max'] = t3lib_div::intInRange(ceil($this->calc($conf['max'])),0);
				if ($conf['begin'] && !$conf['max'])	{
					$conf['max'] = 100000;
				}

				if ($conf['begin'] && $conf['max'])	{
					$queryParts['LIMIT'] = $conf['begin'].','.$conf['max'];
				} elseif (!$conf['begin'] && $conf['max'])	{
					$queryParts['LIMIT'] = $conf['max'];
				}
			}
		}

		if (!$error)	{

				// Setting up tablejoins:
			$joinPart='';
			if ($conf['join'])	{
				$joinPart = 'JOIN ' .trim($conf['join']);
			} elseif ($conf['leftjoin'])	{
				$joinPart = 'LEFT OUTER JOIN ' .trim($conf['leftjoin']);
			} elseif ($conf['rightjoin'])	{
				$joinPart = 'RIGHT OUTER JOIN ' .trim($conf['rightjoin']);
			}

				// Compile and return query:
			$queryParts['FROM'] = trim($table.' '.$joinPart);

				// replace the markers in the queryParts to handle stdWrap
				// enabled properties
			foreach ($queryMarkers as $marker => $markerValue) {
				foreach ($queryParts as $queryPartKey => &$queryPartValue) {
					$queryPartValue = str_replace('###' . $marker . '###',
							$markerValue,
							$queryPartValue);
				}
			}

			$query = $GLOBALS['TYPO3_DB']->SELECTquery(
						$queryParts['SELECT'],
						$queryParts['FROM'],
						$queryParts['WHERE'],
						$queryParts['GROUPBY'],
						$queryParts['ORDERBY'],
						$queryParts['LIMIT']
					);

			return $returnQueryArray ? $queryParts : $query;
		}
	}

	/**
	 * Helper function for getQuery(), creating the WHERE clause of the SELECT query
	 *
	 * @param	string		The table name
	 * @param	array		The TypoScript configuration properties
	 * @param	boolean		If set, the function will return the query not as a string but array with the various parts. RECOMMENDED!
	 * @return	mixed		A WHERE clause based on the relevant parts of the TypoScript properties for a "select" function in TypoScript, see link. If $returnQueryArray is false the where clause is returned as a string with WHERE, GROUP BY and ORDER BY parts, otherwise as an array with these parts.
	 * @access private
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=318&cHash=a98cb4e7e6
	 * @see getQuery()
	 */
	function getWhere($table,$conf, $returnQueryArray=FALSE)	{
		global $TCA;

			// Init:
		$query = '';
		$pid_uid_flag=0;
		$queryParts = array(
			'SELECT' => '',
			'FROM' => '',
			'WHERE' => '',
			'GROUPBY' => '',
			'ORDERBY' => '',
			'LIMIT' => ''
		);

		if (trim($conf['uidInList']))	{
			$listArr = t3lib_div::intExplode(',',str_replace('this',$GLOBALS['TSFE']->contentPid,$conf['uidInList']));
			if (count($listArr)==1)	{
				$query.=' AND '.$table.'.uid='.intval($listArr[0]);
			} else {
				$query.=' AND '.$table.'.uid IN ('.implode(',',$GLOBALS['TYPO3_DB']->cleanIntArray($listArr)).')';
			}
			$pid_uid_flag++;
		}
		// static_* tables are allowed to be fetched from root page
		if (substr($table,0,7)=='static_') {
			$pid_uid_flag++;
		}
		if (trim($conf['pidInList'])) {
			$listArr = t3lib_div::intExplode(',',str_replace('this',$GLOBALS['TSFE']->contentPid,$conf['pidInList']));
				// removes all pages which are not visible for the user!
			$listArr = $this->checkPidArray($listArr);
			if (count($listArr))	{
				$query.=' AND '.$table.'.pid IN ('.implode(',',$GLOBALS['TYPO3_DB']->cleanIntArray($listArr)).')';
				$pid_uid_flag++;
			} else {
				$pid_uid_flag=0;		// If not uid and not pid then uid is set to 0 - which results in nothing!!
			}
		}
		if (!$pid_uid_flag)	{		// If not uid and not pid then uid is set to 0 - which results in nothing!!
			$query.=' AND '.$table.'.uid=0';
		}
		if ($where = trim($conf['where']))	{
			$query.=' AND '.$where;
		}

		if ($conf['languageField'])	{
			if ($GLOBALS['TSFE']->sys_language_contentOL && $TCA[$table] && $TCA[$table]['ctrl']['languageField'] && $TCA[$table]['ctrl']['transOrigPointerField'])	{
					// Sys language content is set to zero/-1 - and it is expected that whatever routine processes the output will OVERLAY the records with localized versions!
				$sys_language_content = '0,-1';
			} else {
				$sys_language_content = intval($GLOBALS['TSFE']->sys_language_content);
			}
			$query.=' AND '.$conf['languageField'].' IN ('.$sys_language_content.')';
		}

		$andWhere = trim($this->stdWrap($conf['andWhere'],$conf['andWhere.']));
		if ($andWhere)	{
			$query.=' AND '.$andWhere;
		}

			// enablefields
		if ($table=='pages')	{
			$query.=' '.$GLOBALS['TSFE']->sys_page->where_hid_del.
						$GLOBALS['TSFE']->sys_page->where_groupAccess;
		} else {
			$query.=$this->enableFields($table);
		}

			// MAKE WHERE:
		if ($query)	{
			$queryParts['WHERE'] = trim(substr($query,4));	// Stripping of " AND"...
			$query = 'WHERE '.$queryParts['WHERE'];
		}

			// GROUP BY
		if (trim($conf['groupBy']))	{
			$queryParts['GROUPBY'] = trim($conf['groupBy']);
			$query.=' GROUP BY '.$queryParts['GROUPBY'];
		}

			// ORDER BY
		if (trim($conf['orderBy']))	{
			$queryParts['ORDERBY'] = trim($conf['orderBy']);
			$query.=' ORDER BY '.$queryParts['ORDERBY'];
		}

			// Return result:
		return $returnQueryArray ? $queryParts : $query;
	}

	/**
	 * Removes Page UID numbers from the input array which are not available due to enableFields() or the list of bad doktype numbers ($this->checkPid_badDoktypeList)
	 *
	 * @param	array		Array of Page UID numbers for select and for which pages with enablefields and bad doktypes should be removed.
	 * @return	array		Returns the array of remaining page UID numbers
	 * @access private
	 * @see getWhere(),checkPid()
	 */
	function checkPidArray($listArr)	{
		$outArr = Array();
		if (is_array($listArr) && count($listArr))	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'uid IN ('.implode(',',$listArr).')'.$this->enableFields('pages').' AND doktype NOT IN ('.$this->checkPid_badDoktypeList.')');
			if ($error = $GLOBALS['TYPO3_DB']->sql_error())	{
				$GLOBALS['TT']->setTSlogMessage($error.': '.$query,3);
			} else {
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					$outArr[] = $row['uid'];
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $outArr;
	}

	/**
	 * Checks if a page UID is available due to enableFields() AND the list of bad doktype numbers ($this->checkPid_badDoktypeList)
	 *
	 * @param	integer		Page UID to test
	 * @return	boolean		True if OK
	 * @access private
	 * @see getWhere(), checkPidArray()
	 */
	function checkPid($uid)	{
		$uid = intval($uid);
		if (!isset($this->checkPid_cache[$uid]))	{
			$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
				'uid',
				'pages',
				'uid=' . intval($uid) .
					$this->enableFields('pages') .
					' AND doktype NOT IN (' . $this->checkPid_badDoktypeList . ')'
			);
			$this->checkPid_cache[$uid] = (bool)$count;
		}
		return $this->checkPid_cache[$uid];
	}

	/**
	 * Builds list of marker values for handling PDO-like parameter markers in select parts.
	 * Marker values support stdWrap functionality thus allowing a way to use stdWrap functionality in various properties of 'select' AND prevents SQL-injection problems by quoting and escaping of numeric values, strings, NULL values and comma separated lists.
	 *
	 * @param	string $table Table to select records from
	 * @param	array $conf Select part of CONTENT definition
	 * @return	array List of values to replace markers with
	 * @access private
	 * @see getQuery()
	 */
	function getQueryMarkers($table, $conf) {

			// parse markers and prepare their values
		$markerValues = array();
		if (is_array($conf['markers.'])) {
			foreach($conf['markers.'] as $dottedMarker => $dummy) {
				$marker = rtrim($dottedMarker, '.');
				if ($dottedMarker == $marker . '.') {
						// parse definition
					$tempValue = $this->stdWrap(
							$conf['markers.'][$dottedMarker]['value'],
							$conf['markers.'][$dottedMarker]
						);
						// quote/escape if needed
					if (is_numeric($tempValue)) {
						if ((int)$tempValue == $tempValue) {
								// handle integer
							$markerValues[$marker] = intval($tempValue);
						} else {
								// handle float
							$markerValues[$marker] = floatval($tempValue);
						}
					} elseif (is_null($tempValue)) {
							// it represents NULL
						$markerValues[$marker] = 'NULL';
					} elseif ($conf['markers.'][$dottedMarker]['commaSeparatedList'] == 1) {
							// see if it is really a comma separated list of values
						$explodeValues = t3lib_div::trimExplode(',', $tempValue);
						if (count($explodeValues) > 1) {
								// handle each element of list separately
							$tempArray = array();
							foreach ($explodeValues as $listValue) {
								if (is_numeric($listValue)) {
									if ((int)$listValue == $listValue) {
										$tempArray[] = intval($listValue);
									} else {
										$tempArray[] = floatval($listValue);
									}
								} else {
										// if quoted, remove quotes before
										// escaping.
									if (preg_match('/^\'([^\']*)\'$/',
											$listValue,
											$matches)) {
										$listValue = $matches[1];
									} elseif (preg_match('/^\"([^\"]*)\"$/',
											$listValue,
											$matches)) {
										$listValue = $matches[1];
									}
									$tempArray[] = $GLOBALS['TYPO3_DB']->fullQuoteStr($listValue, $table);
								}
							}
							$markerValues[$marker] = implode(',', $tempArray);
						} else {
								// handle remaining values as string
							$markerValues[$marker] = $GLOBALS['TYPO3_DB']->fullQuoteStr($tempValue, $table);
						}
					} else {
							// handle remaining values as string
						$markerValues[$marker] = $GLOBALS['TYPO3_DB']->fullQuoteStr($tempValue, $table);
					}
				}
			}
		}
		return $markerValues;
	}


























	/***********************************************
	 *
	 * Frontend editing functions
	 *
	 ***********************************************/

	/**
	 * Generates the "edit panels" which can be shown for a page or records on a page when the Admin Panel is enabled for a backend users surfing the frontend.
	 * With the "edit panel" the user will see buttons with links to editing, moving, hiding, deleting the element
	 * This function is used for the cObject EDITPANEL and the stdWrap property ".editPanel"
	 *
	 * @param	string		A content string containing the content related to the edit panel. For cObject "EDITPANEL" this is empty but not so for the stdWrap property. The edit panel is appended to this string and returned.
	 * @param	array		TypoScript configuration properties for the editPanel
	 * @param	string		The "table:uid" of the record being shown. If empty string then $this->currentRecord is used. For new records (set by $conf['newRecordFromTable']) it's auto-generated to "[tablename]:NEW"
	 * @param	array		Alternative data array to use. Default is $this->data
	 * @return	string		The input content string with the editPanel appended. This function returns only an edit panel appended to the content string if a backend user is logged in (and has the correct permissions). Otherwise the content string is directly returned.
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=375&cHash=7d8915d508
	 */
	function editPanel($content, $conf, $currentRecord='', $dataArr=array()) {

		if($GLOBALS['TSFE']->beUserLogin && ($GLOBALS['BE_USER']->frontendEdit instanceof t3lib_frontendedit)) {
			if(!$currentRecord) {
				$currentRecord = $this->currentRecord;
			}

			if (!count($dataArr)) {
				$dataArr = $this->data;
			}

				// Delegate rendering of the edit panel to the t3lib_frontendedit class.
			$content = $GLOBALS['BE_USER']->frontendEdit->displayEditPanel($content, $conf, $currentRecord, $dataArr);
		}

		return $content;
	}

	/**
	 * Adds an edit icon to the content string. The edit icon links to alt_doc.php with proper parameters for editing the table/fields of the context.
	 * This implements TYPO3 context sensitive editing facilities. Only backend users will have access (if properly configured as well).
	 *
	 * @param	string		The content to which the edit icons should be appended
	 * @param	string		The parameters defining which table and fields to edit. Syntax is [tablename]:[fieldname],[fieldname],[fieldname],... OR [fieldname],[fieldname],[fieldname],... (basically "[tablename]:" is optional, default table is the one of the "current record" used in the function). The fieldlist is sent as "&columnsOnly=" parameter to alt_doc.php
	 * @param	array		TypoScript properties for configuring the edit icons.
	 * @param	string		The "table:uid" of the record being shown. If empty string then $this->currentRecord is used. For new records (set by $conf['newRecordFromTable']) it's auto-generated to "[tablename]:NEW"
	 * @param	array		Alternative data array to use. Default is $this->data
	 * @param	string		Additional URL parameters for the link pointing to alt_doc.php
	 * @return	string		The input content string, possibly with edit icons added (not necessarily in the end but just after the last string of normal content.
	 */
	function editIcons($content, $params, $conf=array(), $currentRecord='', $dataArr=array(), $addUrlParamStr='')	{
		if($GLOBALS['TSFE']->beUserLogin && ($GLOBALS['BE_USER']->frontendEdit instanceof t3lib_frontendedit)) {
			if(!$currentRecord) {
				$currentRecord = $this->currentRecord;
			}

			if (!count($dataArr)) {
				$dataArr = $this->data;
			}

				// Delegate rendering of the edit panel to the t3lib_frontendedit class.
			$content = $GLOBALS['BE_USER']->frontendEdit->displayEditIcons($content, $params, $conf, $currentRecord, $dataArr, $addURLParamStr);
		}

		return $content;
	}


	/**
	 * Returns true if the input table/row would be hidden in the frontend (according nto the current time and simulate user group)
	 *
	 * @param	string		The table name
	 * @param	array		The data record
	 * @return	boolean
	 * @access private
	 * @see editPanelPreviewBorder()
	 */
	function isDisabled($table,$row)	{
		global $TCA;
		if (
			($TCA[$table]['ctrl']['enablecolumns']['disabled'] && $row[$TCA[$table]['ctrl']['enablecolumns']['disabled']]) ||
			($TCA[$table]['ctrl']['enablecolumns']['fe_group'] && $GLOBALS['TSFE']->simUserGroup && $row[$TCA[$table]['ctrl']['enablecolumns']['fe_group']]==$GLOBALS['TSFE']->simUserGroup) ||
			($TCA[$table]['ctrl']['enablecolumns']['starttime'] && $row[$TCA[$table]['ctrl']['enablecolumns']['starttime']] > $GLOBALS['EXEC_TIME']) ||
			($TCA[$table]['ctrl']['enablecolumns']['endtime'] && $row[$TCA[$table]['ctrl']['enablecolumns']['endtime']] && $row[$TCA[$table]['ctrl']['enablecolumns']['endtime']] < $GLOBALS['EXEC_TIME'])
		)	return true;
	}
}
	

?>
/**
 * Main object  
 */
universalUploader = {
		
	/**
	 * Status constants
	 */
	FILE_READY:  0,
	FILE_UPLOADING:  1,
	FILE_COMPLETE:  2,
	FILE_STOPPED:  3,
	FILE_ERROR:  4, 
	/**
	 * Array of available uploaders
	 * Internal array which contain JS Uploader objects
	 */	
	uploaders : [],	
	/**
	 * Default configuration
	 */
	options: {
		uploaders: "html5, flash, html4",
		showAlertOnError: true,
		imagesPath: "images/",
		statusIcons: {
			0:"file_ready.png",
			1:"file_progress.gif",
			2:"file_complete.png",
			3:"file_ready.png",
			4:"file_error.png"
		},
		icons: {
			"add":"add.png",
			"upload":"upload.png",
			"cancel":"cancel.png",
			"clear":"clear.png",
			"remove":"remove.png"
		},
		width: "500",
		height: "250",
		postFields_file : "Filedata",
		postFields_fileId : "fileId",
		postFields_fileSize : "fileSize",
		postFields_filesCount : "filesCount"
		},
	//default English translation
	 /** Available placeholders : 
	 * 0 - percent
	 * 1 - total files count
	 * 2 - total files size
	 * 3 - uploaded files count
	 * 4 - uploaded files size
	 * 5 - bandwidth
	 * 6 - hours left
	 * 7 - minutes left
	 * 8 - seconds left
	 * 9 - elapsed hours
	 * 10 - elapsed minutes
	 * 11 - elapsed seconds
	 * 12 - error message
	 * */
	translation : {
		"constant_notAvailable" : "N/A",
		"constant_bytes" : "B",
		"constant_kiloBytes" : "KB",
		"constant_megaBytes" : "MB",
		"constant_gigaBytes" : "GB",
		"constant_decimalSeparator" :".",
		
		"tabheader_classic" : "Classic",
		"tabheader_drag-and-drop" : "Drag and Drop",
		"tabheader_flash" : "Flash",
		"tabheader_silverlight" : "Silverlight",
		
		"button_browse" : "Browse",
		"button_upload" : "Upload",
		"button_cancel" : "Cancel",
		"button_clear" : "Clear",
		
		"messages_filesCountExceeded": "Only {51} files are allowed to upload! {50} files were ignored!",
		"messages_fileSizeExceeded": "Only files less than {51} are allowed! {50} files were ignored!",
		"messages_totalFileSizeExceeded": "Total size of the files should be less than {51}. {50} files were ignored!",
		"messages_wrongFileType": "Only files with following type: {51} allowed to upload! {50} files were ignored!",
		"messages_disabledType": "File with following types : {51} are not allowed to upload! {50} files were ignored!",
		
		"status_ready" : "Count of files: {1} ({2})",
		"status_uploading" : "Total: {0}% ({3}/{1}) {4}/{2} ({5}/sec, time left: {6}:{7}:{8})",
		"status_complete" : "Upload complete! {3} files ({4}/{2}), Elapsed time: {9}:{10}:{11}",
		"status_error" : "Error: {12}"		
	},
	//current active tab name (type)
	activeTab: "",
	//
	readyBound: false,
	filteredImages: [],
	
	//here initial configuration parameters will be parsed	
	/**
	 * Method should be called to perform initialization for all of configured uploaders
	 * Also it should fire some user-definded event handler to perform some post-init actions.
	 * For example show some messages about supported upload technologies 
	 */
	init : function (options){
			
		var _self = this;
		//_self.currentState = _self.FILE_READY;
		this.files = [];
		if(!options.handlers)
			options.handlers = {};
		
		/**
		 * Event listener for tab clicks
		 * Should change active tab, e.q. set current tab as active and hide all other tabs   
		 * @param e - event object
		 */
		this.tabChange= function (e){
			var id = e.srcElement? e.srcElement.id : this.id;
			var newTab = id.substr(0,id.indexOf('_header'));
			if(_self.activeTab != newTab)
			{
				universalUploader.setSelectedTab(_self.activeTab, false);
				universalUploader.setSelectedTab(newTab, true);
				_self.activeTab = newTab;
				_self.files=_self.uploaders[_self.activeTab].getFiles();
				_self.uploaders[_self.activeTab].afterRender();
				//_self.setCurrentState(_self.FILE_READY);
				_self.getProgressInfo().reset();
				_self.drawList_Refresh(_self.activeTab);
				_self.updateStatus();
			}	
		};
		
		this.applyGrayFilter = function(imgObj, alias, defaultValue)
		{
			if(_self.filteredImages[alias])
				return _self.filteredImages[alias];
			if(imgObj)
			{
				try{
					var canvas = document.createElement('canvas');
			
					var canvasContext = canvas.getContext('2d');
			
					var imgW = imgObj.width;
			
					var imgH = imgObj.height;
					canvas.width = imgW;
					canvas.height = imgH;
					canvasContext.drawImage(imgObj, 0, 0);
			
					var imgPixels = canvasContext.getImageData(0, 0, imgW, imgH);
			
					for(var y = 0; y < imgPixels.height; y++){
					     for(var x = 0; x < imgPixels.width; x++){
					          var i = (y * 4) * imgPixels.width + x * 4;
					          var avg = (imgPixels.data[i] + imgPixels.data[i + 1] + imgPixels.data[i + 2]) / 3;
					          imgPixels.data[i] = avg;
					          imgPixels.data[i + 1] = avg;
					          imgPixels.data[i + 2] = avg;
					     }
					}
					
					canvasContext.putImageData(imgPixels, 0, 0, 0, 0, imgPixels.width, imgPixels.height);
					if(!_self.filteredImages[alias])
						_self.filteredImages[alias] = canvas.toDataURL();
					return canvas.toDataURL();
				}
				catch(e)
				{
					return defaultValue;
				}
			}
			return defaultValue;
		};
		
		this.getCurrentState = function(uploaderId){
			return _self.uploaders[uploaderId? uploaderId : _self.activeTab].currentState;			
		};
		
		this.setCurrentState = function(uploaderId, state){
			_self.uploaders[uploaderId? uploaderId : _self.activeTab].currentState = state;			
		};
		
		this.uploadButtonHandler = function(){
			if(_self.getCurrentState() == _self.FILE_UPLOADING)
				_self.stop();
			else
				_self.upload();			
		};
		/**
		 * Start upload process in current tab
		 */
		this.upload= function(){
			if(_self.options.formName)
			{
				var formFields = _self.getFormFields(_self.options.formName), i = 0;
				for(i = 0; i < formFields.length; i++)
					if (formFields[i][0])
						_self.options.postFields[formFields[i][0]] = formFields[i][1];
			}
			_self.uploaders[_self.activeTab].upload();
		};
		
		this.startUpload = function(){ if(_self.getCurrentState() != _self.FILE_UPLOADING) _self.upload();};
		this.cancelUpload = function(){ _self.stop();};
		this.removeAll = function(){ _self.clearList();};
		/**
		 * Stop upload process in current tab
		 */
		this.stop= function(){
			_self.uploaders[_self.activeTab].stop();
		};
		
		this.getProgressInfo = function(id)
		{
			return _self.uploaders[(id == undefined ? _self.activeTab : id)].progressInfo;
		};
		/**
		 * Clear file list 
		 */
		this.clearList = function(){			
			var i =0;
			var cbid = document.getElementById("clearButton_"+_self.activeTab);
			if(!cbid || (cbid && cbid.className.indexOf("uuButtonDisabled") <0 ))
			{
				if(_self.getCurrentState() == _self.FILE_UPLOADING)
					_self.stop();
				_self.uploaders[_self.activeTab].clearList();
				/*
				for(i = _self.files.length-1; i >= 0; i--)
					_self.removeFile(_self.files[i].id, true);
				*/
			}
		};
		
		/**
		 * Remove file by id
		 * @param fileId
		 */
		this.removeFile= function(fileId, doNotCallEventHandler){
			if(_self.getCurrentState() != _self.FILE_UPLOADING)
			{
				_self.uploaders[_self.activeTab].removeFile(fileId, doNotCallEventHandler);
				_self.removeListItem(fileId);
			}
		};
		/**
		 * RemoveItem from list 
		 * @param fileId - unique identificator of file
		 */
		this.removeListItem=function(fileId){
			var li = document.getElementById(fileId+'_listItem');
			if(li)
				li.parentNode.removeChild(li);
		};	
		
		/**
		 * Return array of files of specified uploader
		 * @param uploaderId
		 * @returns
		 */
		this.getFiles = function(uploaderId){
			return _self.uploaders[uploaderId != undefined ? uploaderId : _self.activeTab].getFiles();
		};
		
		/**
		 * Return count of files
		 * @param uploaderId
		 * @returns
		 */
		this.getFilesCount = function(uploaderId){
			return _self.getFiles(uploaderId).length;
		};
		
		/**
		 * Return file with specified id of specified uploader 
		 * @returns
		 */
		this.getFile = function(){
			return _self.getFiles(arguments[1])[arguments[0]];
		};
		/**
		 * Remove by element
		 * @param item
		 */
		this.removeFileItem= function(item){
			var fileId = this.id.substr(0,this.id.indexOf('_remove'));;
			_self.removeFile(fileId);
		};
		/**
		 * Render interface . we should wait for page load to call this method
		 */
		this.render = function(forceRender){
			if ( !_self.isReady || forceRender == true){
				// Remember that the DOM is ready
				universalUploader.isReady = true;
			
				//Where to place content
				var holder = document.body;
				//get holder from options
				if(_self.options.holder)
					holder = document.getElementById(_self.options.holder);
				/*if(this.options.width)
					holder.style.width = this.options.width+'px';
				if(this.options.height)
					holder.style.height = this.options.height+'px';*/
				tabContainer = document.createElement('dl');
				tabContainer.className="tabs";
				tabContainer.id="tabs_container";
				holder.appendChild(tabContainer);
				//Render all available uploaders
				var i=0;
				if(_self.uploaders)
				{
					//for (i = 0; i < universalUploader.uploaders.length; i++)
					for (key in _self.uploaders)
					{
						if (_self.uploaders.hasOwnProperty(key) && typeof _self.uploaders[key] != "function")
						{
							_self.addTabTo(tabContainer, _self.uploaders[key], i==0);
							i++;
						}
					}
					
				}
				if(i >0 )
				{
					_self.updateStatus();
					if(!forceRender)
						_self.callEventHandel("Init", true);
				}
				else
					_self.callEventHandel("Init", false);
			}
		};
		/**
		 * Add new tab to the page
		 * @param holder - component holder
		 * @param uploader - uploader to add
		 * @param selected - true if tab should beselected by default
		 */
		this.addTabTo = function (holder, uploader, selected){
			if(selected)
				_self.activeTab = uploader.type;
			tabHeader = document.createElement('dt');
			tabHeader.id = uploader.type+"_header";
			tabHeader.innerHTML = this.getTranslatedString('tabheader_'+uploader.type);
			tabHeader.className = selected ? "selected" :"";
			_self.addEventListener("click", tabHeader, this.tabChange);
			holder.appendChild(tabHeader);
			
			tabContents = document.createElement('div');
			tabContents.id = uploader.type+"_content";
			tabContents.className = "tab-content";
			if(_self.options.width)
				tabContents.style.width = (_self.options.width-22)+'px';
			if(_self.options.height)
				tabContents.style.height = (_self.options.height-52)+'px';
			var inner = uploader.render();
			if(!uploader.isProgressVisible || (uploader.isProgressVisible && uploader.isProgressVisible()))
			{
				//Add footer with progress bar and status text
				inner += 
							'<div id="'+uploader.type+'_statusPanel" class="uuStatusContainer">' +
									'<div id="'+uploader.type+'_statusLabel" class="uuStatusLabel">Here goes status info</div>' +
									'<div id="'+uploader.type+'_progressBar" class="uuProgressBar">' +
									'<div id="'+uploader.type+'_progressBarBody" class="uuProgressBarBody"></div>' +
								'</div>' +
							'</div>' ;
			}
			tabContents.innerHTML = inner;
			tabBody = document.createElement('dd');
			tabBody.id = uploader.type+"_body";
			tabBody.className = selected ? "selected" :"";
			
			tabBody.appendChild(tabContents);
			
			holder.appendChild(tabBody);
			if(_self.options.width)
				holder.style.width = _self.options.width+'px';
			if(_self.options.height)
				holder.style.height = _self.options.height+'px';
			if(selected)
				uploader.afterRender();
			_self.drawList_InitialDraw(uploader.type);
		};
		
		this.getWidth = function(width){
			return parseInt(_self.options.width);
		};
		
		this.getHeight = function(height){
			return parseInt(_self.options.height);
		};
		
		this.setWidth = function(width){
			_self.setSize(width, _self.options.height);
		};
		
		this.setHeight = function(height){
			_self.setSize(_self.options.width, height);
		};
		
		this.setSize = function(width, height, redrawList){
			//Where to place content
			/*var holder = document.body;
			//get holder from options
			if(_self.options.holder)*/
				holder = document.getElementById("tabs_container");//_self.options.holder);
			_self.options.width = width;
			_self.options.height = height;
			if(_self.options.width)
				holder.style.width = _self.options.width+'px';
			if(_self.options.height)
				holder.style.height = _self.options.height+'px';
			if(_self.uploaders)
			{
				//for (i = 0; i < universalUploader.uploaders.length; i++)
				for (key in _self.uploaders)
				{
					if (_self.uploaders.hasOwnProperty(key) && typeof _self.uploaders[key] != "function")
					{
						var tabContents = document.getElementById(_self.uploaders[key].type+"_content");
						var li = document.getElementById(_self.uploaders[key].type+"_listItemsHolder"), lid = document.getElementById(_self.uploaders[key].type+"_fileList");
						
						if(_self.options.width)
							tabContents.style.width = (_self.options.width-22 > 0 ? _self.options.width-22 : 1)+'px';
						if(_self.options.height)
						{						
							tabContents.style.height = (_self.options.height-52 > 0? _self.options.height-52 : 1)+'px';
							lid.style.height=Number(_self.options.height-115 > 0? _self.options.height-115 : 1)+'px';
							//fix for IE browser
							li.style.height=lid.style.height;
						}
						if(redrawList)
							_self.drawList_Redraw(_self.uploaders[key].type);
					}
				}
				
			}
		};
		
		/**
		 * Add all available uploaders to the array
		 */
		this.addAllUploaders = function(){
			_self.addUploader(universalUploader.Html4);
			_self.addUploader(universalUploader.Html5);
			_self.addUploader(universalUploader.Flash);
			_self.addUploader(universalUploader.Silverlight);	
		};
		/**
		 * Adds uploader to the array if it was correctly initialized
		 * @param upl
		 */
		this.addUploader = function(upl){
			if(upl && upl.available)
			{
				_self.uploaders[upl.type] = upl;
				//_self.uploaders.push(upl);
			}
		};
		/**
		 * return localized string
		 * @param name - name of parameter
		 * @returns translated string
		 */
		this.getTranslatedString = function(name){
			return _self.translation[name];
		};
		this.getFileStat = function(){
			var stat = [_self.files.length,0,0,0];
			for( i = 0; i < _self.files.length; i++)	
			{
				stat[1] += _self.files[i].size > 0 ? _self.files[i].size : 0;
				if(_self.files[i].status == _self.FILE_COMPLETE)
				{
					stat[2]++;
					stat[3] += _self.files[i].size > 0 ? _self.files[i].size : 0;
				}
			}
			return stat;
		};
		
		this.bindEventListener = function(eventName, func){
			if(_self.options)
				_self.options.handlers[eventName] = func;			
		};
		
		this.unbindEventListener = function(eventName){
			if(_self.options && _self.options.handlers[eventName])
			{
				_self.options.handlers[eventName] = null;
				delete _self.options.handlers[eventName]; 
			}
		};
		
		this.callEventHandel = function(eventName){
			if(_self.options && _self.options.handlers[eventName] && typeof _self.options.handlers[eventName] == 'function')
			{
				//remove event name from arguments
				var evntArgs = [];
				for(var i=1 ; i < arguments.length; i++)
					evntArgs.push(arguments[i]);
				try{
					return _self.options.handlers[eventName].apply(this, evntArgs);
				}
				catch(e){alert(e);}
				return null;
			}
		};
		
		//Event handlers
		/**
		 * Method called when user select files for upload
		 * @param addedFiles
		 */
		this.onAddFiles = function(uploaderId, addedFiles){
			//concatenate arrays  
			//_self.files = _self.files.concat(_self.files, addedFiles);
			//call user defined function here
			//refresh file list
			//duplicate array item for easy access by id
			/*_self.files[addedFiles[i].id] = addedFiles[i];
			_self.files.push(addedFiles[i]);
			*/
			if(_self.getCurrentState(uploaderId) != _self.FILE_UPLOADING)
				_self.setCurrentState(uploaderId, _self.FILE_READY);
			_self.files = _self.getFiles(_self.activeTab);
			if(uploaderId == _self.activeTab)
				for( i = 0; i < addedFiles.length; i++)	
					_self.drawList_AddFile(addedFiles[i]);
			if(_self.getCurrentState(uploaderId) != _self.FILE_UPLOADING)
				_self.getProgressInfo(uploaderId).reset();
			else
				_self.getProgressInfo(uploaderId).resetStat();
			
			/*
			 * не во всех загрузчиках можно добавлять файлы в очередь загрузки когда она уже началась
			 * поэтому добавленные файлы будут загружены только при следующем нажатии кнопки аплоад
			 * else
				_self.uploaders[uploaderId].appendToUploadQueue(addedFiles);
			*/
			if(uploaderId == _self.activeTab)
				_self.updateStatus();
			_self.callEventHandel("FilesAdded", uploaderId, addedFiles);
		};
		/**
		 * Method called when file removed from list
		 */
		this.onRemoveFile = function(uploaderId, fileId, doNotCallHandler){
			//_self.drawList_RemoveFile(fileId);
			//To be sure that file is removed from list
			_self.removeListItem(fileId);
			_self.setCurrentState(uploaderId, _self.FILE_READY);
			var file = _self.getFile(fileId, uploaderId);
			if(file)
			{
				_self.getProgressInfo(uploaderId).totalFiles--;
				if(file.size > 0)
					_self.getProgressInfo(uploaderId).totalSize-=file.size;
				if(file.status == universalUploader.FILE_COMPLETE)
				{
					_self.getProgressInfo(uploaderId).uploadedFiles--;
					if(file.size > 0)
						_self.getProgressInfo(uploaderId).uploadedSize-=file.size;
				}
				/*_self.files.splice(_self.files.indexOf(file),1);
				delete _self.files[fileId];
				file = null;*/
			}			
			if(uploaderId == _self.activeTab)
				_self.updateStatus();
			if(!doNotCallHandler)
				_self.callEventHandel("FilesRemoved", uploaderId, [file]);
		};
		
		/**
		 * Method called when file removed from list
		 */
		this.onClearList = function(uploaderId){
			var _f = _self.getFiles(uploaderId);
			if(_f.length > 0)
				_self.callEventHandel("FilesRemoved", uploaderId, _f);
		};
		
		this.onError = function(uploaderId, message){
			
			if(_self.options.showAlertOnError)
				_self.showAlert(message);
			_self.getProgressInfo(uploaderId).lastError = message;
			_self.callEventHandel("Error", uploaderId, message);
		}; 
		
		this.onUploadStart = function(uploaderId){
			_self.setCurrentState(uploaderId, _self.FILE_UPLOADING);
			_self.getProgressInfo(uploaderId).reset();
			if(uploaderId == _self.activeTab)
				_self.updateStatus();
			
			/*var uploadBtn = document.getElementById("uploadButton_"+_self.activeTab);
			if(uploadBtn)			
				uploadBtn.innerHTML='<span><span><img src="'+universalUploader.getIcon("cancel")+'"/>'+_self.getTranslatedString("button_cancel")+"</span></span>";
			*/
			_self.drawList_Refresh(uploaderId);
			_self.callEventHandel("UploadStart", uploaderId);
		};
		
		this.onUploadComplete = function(uploaderId){
			_self.setCurrentState(uploaderId, _self.FILE_COMPLETE);
			if(uploaderId == _self.activeTab)
				_self.updateStatus();
			/*var uploadBtn = document.getElementById("uploadButton_"+_self.activeTab);
			if(uploadBtn)
				uploadBtn.innerHTML='<span><span><img src="'+universalUploader.getIcon("upload")+'"/>'+_self.getTranslatedString("button_upload")+"</span></span>";
			*/
			_self.drawList_Refresh(uploaderId);
			_self.callEventHandel("UploadComplete", uploaderId);
			if(_self.options.redirectUrl)
				window.location = _self.options.redirectUrl;
		};
		
		this.onUploadStop = function(uploaderId){
			_self.getProgressInfo(uploaderId).stopTime = new Date();
			if(_self.getCurrentState(uploaderId) != _self.FILE_ERROR)
			{
				_self.setCurrentState(uploaderId, _self.FILE_READY);
			
				if(uploaderId == _self.activeTab)
					_self.updateStatus();
			}
			
			/*var uploadBtn = document.getElementById("uploadButton_"+_self.activeTab);
			if(uploadBtn)			
				uploadBtn.innerHTML='<span><span><img src="'+universalUploader.getIcon("upload")+'"/>'+_self.getTranslatedString("button_upload")+"</span></span>";
			*/
			_self.drawList_Refresh(uploaderId);
			_self.callEventHandel("UploadStop", uploaderId);
		};
		/**
		 * Method called when upload process of individual file started 
		 * @param file
		 */
		this.onFileUploadStart = function(uploaderId, fileId){
			_self.setCurrentState(uploaderId,_self.FILE_UPLOADING);
			var file = _self.getFile(fileId, uploaderId);
			//reset progress info vars
			//_self.getProgressInfo(uploaderId).lastProgressTime = new Date();
			//_self.getProgressInfo(uploaderId).lastBwStore = new Date();			
			_self.getProgressInfo(uploaderId).lastBytes = 0;
			if(file){
				file.status = universalUploader.FILE_UPLOADING;
	            file.bytesLoaded = 0;
	            file.error = "";
	            _self.drawList_RedrawFile(uploaderId, file);
			}
			if(uploaderId == _self.activeTab)
				_self.updateStatus();
			_self.callEventHandel("FileUploadStart", uploaderId, file);
		};
		/**
		 * Method called when information about individual file progress received
		 * @param fileId - file identificator 
		 */
		this.onFileUploadProgress = function(uploaderId, fileId, loaded){
			if(_self.getCurrentState(uploaderId) != _self.FILE_STOPPED)
				_self.setCurrentState(uploaderId,_self.FILE_UPLOADING);
			var file = _self.getFile(fileId, uploaderId);
			//Update also progressinfo object to calculate estimate time left, badwidth, etc 
			if(file){
				file.bytesLoaded = loaded;
				_self.drawList_RedrawFile(uploaderId, file);
			}
			//Update progress information
			//console.log(file.name+" - "+loaded);
			_self.getProgressInfo(uploaderId).onProgress(loaded);
			if(uploaderId == _self.activeTab)
				_self.updateStatus();
			_self.callEventHandel("UploadProgress", uploaderId, file, _self.getProgressInfo(uploaderId));
		};
		/**
		 * Called when error occured during file upload 
		 * @param fileId - file identificator
		 */
		this.onFileUploadError = function(uploaderId, fileId, status, msg){
			_self.setCurrentState(uploaderId, _self.FILE_ERROR);
			var file = _self.getFile(fileId, uploaderId);
			if(file){
				file.status = universalUploader.FILE_ERROR;
	            file.bytesLoaded = 0;
	            file.error = status + ". " + msg + ".";
	            _self.drawList_RedrawFile(uploaderId, file);
			}
			_self.getProgressInfo(uploaderId).lastError = msg;
			if(uploaderId == _self.activeTab)
				_self.updateStatus();
			_self.callEventHandel("FileUploadError", uploaderId, file, status, msg);
		};
		/**
		 * Called when file upload aborted
		 * @param fileId
		 */
		this.onFileUploadStop = function(uploaderId, fileId){
			var file = _self.getFile(fileId, uploaderId);
			if(file){
				if(_self.getCurrentState(uploaderId) != universalUploader.FILE_ERROR)
					file.status = universalUploader.FILE_STOPPED;
	            file.bytesLoaded = 0;
	            file.error = "";
	            _self.drawList_RedrawFile(uploaderId, file);
			}			
			
			_self.onUploadStop(uploaderId);		
			_self.callEventHandel("FileUploadStop", uploaderId, file);
		};
		
		/**
		 * Called when file upload aborted
		 * @param fileId
		 */
		this.onFileUploadComplete = function(uploaderId, fileId, response){
			var file = _self.getFile(fileId, uploaderId);		
			_self.getProgressInfo(uploaderId).uploadedFiles++;
			/*if(!response)
				response = "";
				*/
			if(file){
				file.serverResponse = response;
				if(_self.getCurrentState(uploaderId) != universalUploader.FILE_ERROR)
					file.status = universalUploader.FILE_COMPLETE;
		        file.bytesLoaded = file.size;
		        file.error = "";
		        _self.onFileUploadProgress(uploaderId, fileId, file.size);
		        //_self.drawList_RedrawFile(file);
			}		
			if(uploaderId == _self.activeTab)
				_self.updateStatus();
			_self.callEventHandel("FileUploadComplete", uploaderId, file, response);
		};
		//end of event handlers
		
			
		//List draw methods
		/**
		 * Return path to the status icon
		 */
		this.getFileStateIcon = function(status){
			return _self.options.imagesPath+_self.options.statusIcons[status];
		};
		/**
		 * Return path to the status icon
		 */
		this.getIcon = function(name){
			return _self.options.imagesPath+_self.options.icons[name];
		};
		/**
		 * Init file list. Render required elementse 
		 */
		this.drawList_InitialDraw = function(type){
			if(type)
			{
				var lid = document.getElementById(type+"_fileList");
				if(lid)
				{
					lid.innerHtml="";
					var li = document.createElement('ul');
					li.id=type+'_listItemsHolder';
					li.className='listItemsHolder';
					//lid.style.width=_self.options.width+'px';
					lid.style.height=(_self.options.height-115)+'px';
					//fix for IE browser
					li.style.height=lid.style.height;
					/*var pid = document.getElementById(type+"_statusLabel");
					if(pid)
						pid.style.width = lid.offsetWidth+'px';*/
					lid.appendChild(li);
				}
			}
		};
		
		/**
		 * Redraw file list . All items removed and drawn new
		 */
		this.drawList_Redraw = function(type){
			if(!type)
				type = _self.activeTab;
			if(type)
			{
				var lid = document.getElementById(type+"_listItemsHolder");
				if(lid)
				{
					lid.innerHtml="";
					while (lid.hasChildNodes()) {
						lid.removeChild(lid.lastChild);
					}
					var files = _self.getFiles(type);
					for( i = 0; i < files.length; i++)	
						_self.drawList_AddFile(files[i]);					
				}
			}
		};
		
		this.setButtonsStates = function(type){
			var enabled = !(_self.getCurrentState(type) == _self.FILE_UPLOADING);
			_self.setButtonState(type, "clearButton_", "clear", enabled);
			var uploadBtn = document.getElementById("uploadButton_"+_self.activeTab);
			if(uploadBtn)
				if(enabled)
					uploadBtn.innerHTML='<span><span><img src="'+universalUploader.getIcon("upload")+'"/>'+_self.getTranslatedString("button_upload")+"</span></span>";
				else
					uploadBtn.innerHTML='<span><span><img src="'+universalUploader.getIcon("cancel")+'"/>'+_self.getTranslatedString("button_cancel")+"</span></span>";
			//_self.setButtonState(type, "browseButton_", "add", enabled);
		};
		
		this.setButtonState = function(type, id, iconName, state){
			if(state)
				_self.setButtonEnabled(type, id, iconName);
			else
				_self.setButtonDisabled(type, id, iconName);
		};
		
		this.setButtonEnabled = function(type, id, iconName){
			//get button element
			var bid = document.getElementById(id+type);
			if(bid && bid.className.indexOf("uuButtonDisabled") >=0)
			{
				//remove class
				_self.removeClass(bid, "uuButtonDisabled");
				//restore icon
				var object = bid.getElementsByTagName('img');
				for(var i=0;object[i];i++){
					object[i].src = universalUploader.getIcon(iconName);
				}
			}
		};
		
		this.setButtonDisabled = function(type, id, iconName){
			//get button element
			var bid = document.getElementById(id+type);
			if(bid && bid.className.indexOf("uuButtonDisabled") < 0)
			{
				//remove class
				_self.addClass(bid, "uuButtonDisabled");
				//apply grayscale to 
				var object = bid.getElementsByTagName('img');
				for(var i=0;object[i];i++){
					object[i].src = universalUploader.applyGrayFilter(object[i], id, universalUploader.getIcon(iconName));
				}
			}
		};
		
		/**
		 * Refresh file list without removing existing items.
		 * also check buttons and set them disabled/enabled if needed.
		 */
		this.drawList_Refresh = function(type){
			if(!type)
				type = _self.activeTab;
			if(type)
			{
				_self.setButtonsStates(type);
				var lid = document.getElementById(type+"_listItemsHolder");
				if(lid)
				{					
					var files = _self.getFiles(type);
					for( i = 0; i < files.length; i++)	
						_self.drawList_RedrawFile(type, files[i]);					
				}
			}
		};
		
		/**
		 * Add file to the file list
		 * @param file
		 */
		this.drawList_AddFile = function(file){
			if(_self.activeTab)
			{
				var lid = document.getElementById(_self.activeTab+"_listItemsHolder");
				if(lid)
				{
					var width = lid.offsetWidth;
					var li = document.createElement('li');
					li.id=file.id+'_listItem';
					li.className='listItem';					
					var fileStateDiv = document.createElement('div');
					fileStateDiv.id = file.id+'_fileState';
					fileStateDiv.className = 'fileState';
					fileStateDiv.innerHTML = '<img src="'+_self.getFileStateIcon(file.status)+'">';
					li.appendChild(fileStateDiv);
					
					var fileNameDiv = document.createElement('div');
					fileNameDiv.id = file.id+'_fileName';
					fileNameDiv.className = 'fileName';
					fileNameDiv.style.width = (width-152)+'px';
					fileNameDiv.innerHTML = '<span>'+file.name+'</span>';
					li.appendChild(fileNameDiv);
					
					var fileRemoveDiv = document.createElement('div');
					fileRemoveDiv.id = file.id+'_fileRemove';
					fileRemoveDiv.className = 'fileRemove';
					if(file.status == _self.FILE_UPLOADING || _self.getCurrentState() == _self.FILE_UPLOADING)
						fileRemoveDiv.innerHTML = '<img id="_rmIcon" title="Remove file" src="'+universalUploader.applyGrayFilter(document.getElementById("_rmIcon"), "rmIcon", universalUploader.getIcon("remove"))+'" >';
					else
						fileRemoveDiv.innerHTML = '<img id="_rmIcon" title="Remove file" src="'+universalUploader.getIcon("remove")+'" onClick="javascript: universalUploader.removeFile(\''+file.id+'\')">';

					li.appendChild(fileRemoveDiv);
					var fileSizeDiv = document.createElement('div');
					fileSizeDiv.id = file.id+'_fileSize';
					fileSizeDiv.className = 'fileSize';
					fileSizeDiv.innerHTML = _self.formatBytes(file.size);
					li.appendChild(fileSizeDiv);
					var fileStatusDiv = document.createElement('div');
					fileStatusDiv.id = file.id+'_fileStatus';
					fileStatusDiv.className = 'fileStatus';
					fileStatusDiv.innerHTML = file.getPercent()+'%';
					li.appendChild(fileStatusDiv);
					
					var fileSpaceDiv = document.createElement('div');
					fileSpaceDiv.id = file.id+'_fileSpacer';
					fileSpaceDiv.className = 'fileSpacer';
					fileSpaceDiv.innerHTML = "&nbsp;";
					li.appendChild(fileSpaceDiv);
					lid.appendChild(li);
				}
			}
		};
		
		/**
		 * Redraw file item
		 * @param file
		 */
		this.drawList_RedrawFile = function(uploaderId, file){
			if(uploaderId == _self.activeTab)
			{
				var lid = document.getElementById(_self.activeTab+"_listItemsHolder");
				var fid = document.getElementById(file.id+"_listItem");
				if(lid && fid && fid.offsetTop>fid.offsetHeight)
					lid.scrollTop = fid.offsetTop-fid.offsetHeight;
				var fileStatusDiv = document.getElementById(file.id+'_fileStatus');
				if(fileStatusDiv)
					fileStatusDiv.innerHTML = file.getPercent()+'%';
				var fileStateDiv = document.getElementById(file.id+'_fileState');
				var inner = '<img src="'+_self.getFileStateIcon(file.status)+'">';
				if(fileStateDiv && fileStateDiv.innerHTML != inner)
					fileStateDiv.innerHTML = inner;
				
				var fileRemoveDiv = document.getElementById(file.id+'_fileRemove');
				if(fileRemoveDiv)
					if(file.status == _self.FILE_UPLOADING || _self.getCurrentState() == _self.FILE_UPLOADING)
						fileRemoveDiv.innerHTML = '<img id="_rmIcon" title="Remove file" src="'+universalUploader.applyGrayFilter(document.getElementById("_rmIcon"), "rmIcon", universalUploader.getIcon("remove"))+'" >';
					else
						fileRemoveDiv.innerHTML = '<img id="_rmIcon" title="Remove file" src="'+universalUploader.getIcon("remove")+'" onClick="javascript: universalUploader.removeFile(\''+file.id+'\')">';					
			}			
		};
		//end of List draw methods
		
		/**
		 * Update total progress bar
		 */
		this.updateStatus = function(){
			var totalProgress = document.getElementById(_self.activeTab+'_progressBarBody');
			if(totalProgress)
				totalProgress.style.width = _self.getProgressInfo().getTotalPercent()+'%';
			var statusLabel = document.getElementById(_self.activeTab+'_statusLabel');
			if(statusLabel)
				switch(_self.getCurrentState())
				{
					case _self.FILE_READY:
						statusLabel.innerHTML = _self.getProgressInfo().replacePlaceHolders(_self.getTranslatedString("status_ready"));
						break;
					case _self.FILE_UPLOADING:
						statusLabel.innerHTML = _self.getProgressInfo().replacePlaceHolders(_self.getTranslatedString("status_uploading"));
						break;
					case _self.FILE_COMPLETE:
						statusLabel.innerHTML = _self.getProgressInfo().replacePlaceHolders(_self.getTranslatedString("status_complete"));
						break;
					case _self.FILE_ERROR:
						statusLabel.innerHTML = _self.getProgressInfo().replacePlaceHolders(_self.getTranslatedString("status_error"));
						break;
				}
		};
		/**
		 * Place from over browse button to guarantee click on input field 
		 * @param browseButton
		 * @param form
		 * @param input
		 */
		this.positionFormUnderButton = function(browseButton, form, input){
			if(browseButton && form ) 
			{					
				form.style.position = 'absolute';
				form.style.width = browseButton.offsetWidth+'px';
				form.style.height = browseButton.offsetHeight+'px';
				form.style.overflowX = 'hidden';
				form.style.overflowY = 'hidden';
				 var topCoordinate = browseButton.offsetTop;
				 var leftCoordinate = browseButton.offsetLeft;
				 /*var obj = browseButton;
				 while(obj != container)
				 {
					  topCoordinate += obj.offsetTop;
					  leftCoordinate += obj.offsetLeft;
					  obj = obj.offsetParent;
				 }  */  
				 form.style.top = topCoordinate+'px';
				 form.style.left = leftCoordinate+'px';				 
			}
			if(input)
			 {
				var onClick = function(e) {
				 	{
						input.click();
						try{
							if (e.preventDefault) e.preventDefault();
					        if (e.stopPropagation) e.stopPropagation();
					        if (window.event) window.event.returnValue = false;
					        if (e.stopEvent) e.stopEvent();
						}
						catch(er){}
			 		}
				};
				var onOver = function(e) {
					universalUploader.addClass(browseButton, 'uuButtonHover');
				},
				onOut = function(e) {
					universalUploader.removeClass(browseButton, 'uuButtonHover');
				},
				onDown = function(e) {
					universalUploader.addClass(browseButton, 'uuButtonActive');
				},
				onUp = function(e) {
					universalUploader.removeClass(browseButton, 'uuButtonActive');
				};
				
				universalUploader.removeEventListener('click', browseButton, onClick);
				universalUploader.addEventListener('click', browseButton, onClick);
				universalUploader.removeEventListener('mouseover', input, onOver);
				universalUploader.addEventListener('mouseover', input, onOver);
				universalUploader.removeEventListener('mouseout', input, onOut);
				universalUploader.addEventListener('mouseout', input, onOut);
				universalUploader.removeEventListener('mousedown', input, onDown);
				universalUploader.addEventListener('mousedown', input, onDown);
				universalUploader.removeEventListener('mouseup', input, onUp);
				universalUploader.addEventListener('mouseup', input, onUp);
			 }
			//Now we should place input form over browse button or under it
			/*var zindex=0, zind;
	        var obj = browseButton;
	        var comp;

	        while (obj.tagName != 'BODY')
	        {
	            comp = obj.currentStyle ? obj.currentStyle : getComputedStyle(obj, null);
	            zind = parseInt(comp.zIndex);
	            zind = isNaN(zind) ? 0 : zind;
	            zindex += zind + 1;
	            obj = obj.parentNode;
	        }

	        form.style.zIndex = zindex+2;
	        input.style.zIndex = zindex+2;
	        browseButton.style.zIndex = zindex;*/

		};
		/**
		 * Convert bytes to human readable format 
		 */
		this.formatBytes = function(bytes, round)
		{			
			var sep = _self.getTranslatedString("constant_decimalSeparator") ;
			sep = sep ? sep : '.';
			var decim = round == true ? 1: 0;
			if(bytes < 0)
				return _self.getTranslatedString("constant_notAvailable");
			if(bytes <1024)			
				return _self.formatNumber(bytes, decim, sep)+" "+_self.getTranslatedString("constant_bytes");			
			if(bytes>=1024 && bytes/1024<1024)
				return _self.formatNumber(bytes/1024, decim, sep)+" "+_self.getTranslatedString("constant_kiloBytes");
			if(bytes/1024>=1024 && bytes/1024/1024<1024)
				return _self.formatNumber(bytes/1024/1024, decim, sep)+" "+_self.getTranslatedString("constant_megaBytes");			
			if(bytes/1024/1024>=1024)
				return _self.formatNumber(bytes/1024/1024/1024, decim, sep)+" "+_self.getTranslatedString("constant_gigaBytes");
			return bytes+" "+_self.getTranslatedString("constant_bytes");
		};
		/**
		 * Method check if file have valid extension
		 * @param fileName - name of file
		 * @param fileTypes - array of allowed extensions
		 * @returns {Boolean}
		 */
		this.isExtensionInArray = function (fileName, fileTypes){
			var dotIndex = 0, ext = "";	
			dotIndex = fileName.lastIndexOf(".");
			if(dotIndex >= 0)
			{
				ext = fileName.substring(dotIndex+1).toLowerCase();			
				if(fileTypes.indexOf(ext) >=0 || fileTypes == undefined || fileTypes.length == 0)
					return true;				
				return false;
			}
			return false;
		};
		/**
		 * Check if we can add file to the list
		 * @param file
		 * @param listArray
		 * @param tSize
		 * @returns {Number}
		 */
		this.isValidFile = function(file, listArray, tSize){
			try{
				if (_self.options.fileFilter_maxSize != -1 && file.size > _self.options.fileFilter_maxSize) 
					return 4;			
				else if (_self.options.fileFilter_maxTotalSize != -1 && (_self.getProgressInfo().totalSize+tSize+file.size) > _self.options.fileFilter_maxTotalSize) 
					return 7;
				else if (_self.options.fileFilter_types && !_self.isExtensionInArray(file.name, _self.options.fileFilter_types.split(","))) 
					return 8;
				else if (_self.options.fileFilter_minSize != -1 && file.size < _self.options.fileFilter_minSize) 
					return 9;
				else if(_self.options.fileFilter_disabledTypes && _self.isExtensionInArray(file.name, _self.options.fileFilter_disabledTypes.split(","))) 
					return 10;
				else  if (_self.options.fileFilter_maxCount != -1 && (_self.files.length + listArray.length +1) > _self.options.fileFilter_maxCount)
					return 2;
			}
			catch(e){
				return -1;
			}
			return -1;
		};
		
		/**
		 * Analyze result of file addition and show error message if needed.  
		 * @param invalidFilesCount - array of invalid files. Each element is count of invalid files for certain condition 
		 */
		this.displayResultOfAdd = function(invalidFilesCount){
			var i=0;
			for(i=2; i < invalidFilesCount.length; i++)
			{
				if(invalidFilesCount[i] > 0)
					switch(i)
					{
						case 2:
								_self.onError(_self.activeTab,_self.replaceMessagesPlaceHolders(_self.getTranslatedString("messages_filesCountExceeded"),
										_self.options.fileFilter_maxCount, invalidFilesCount[i]));			
								break;					
						case 4:								
								_self.onError(_self.activeTab,_self.replaceMessagesPlaceHolders(_self.getTranslatedString("messages_fileSizeExceeded"),
										_self.formatBytes(_self.options.fileFilter_maxSize), invalidFilesCount[i]));
								break;						
						case 7:
								_self.onError(_self.activeTab,_self.replaceMessagesPlaceHolders(_self.getTranslatedString("messages_totalFileSizeExceeded"),
									_self.formatBytes(_self.options.fileFilter_maxTotalSize), invalidFilesCount[i]));										
								break;
						case 8:
								_self.onError(_self.activeTab,_self.replaceMessagesPlaceHolders(_self.getTranslatedString("messages_wrongFileType"),
									_self.options.fileFilter_types, invalidFilesCount[i]));									
								break;
						case 9:
								_self.onError(_self.activeTab,_self.replaceMessagesPlaceHolders(_self.getTranslatedString("messages_fileSizeNotEnough"),
										_self.formatBytes(_self.options.fileFilter_maxSize), invalidFilesCount[i]));								
								break;
						case 10:
								_self.onError(_self.activeTab,_self.replaceMessagesPlaceHolders(_self.getTranslatedString("messages_disabledType"),
									_self.options.fileFilter_disabledTypes, invalidFilesCount[i]));
									
								break;
					}
			}
		};
		/**
		 * Function replace placeholders in error messages
		 */
		this.replaceMessagesPlaceHolders = function(pattern, condition, invalidFilesCount){
			var ret = pattern.replace(/\{50\}/g, invalidFilesCount);
			ret = ret.replace(/\{51\}/g, condition);			
			return ret;
		};
		/**
		 * Display alert
		 * @param message
		 */
		this.showAlert = function(message){
			alert(message);			
		};
		/**
		 * Download laguage from server using Ajax request
		 * @param url
		 */
		/*this.getLanguage= function(url){
			try{
				var xhr = new XMLHttpRequest();
				   
		        xhr.upload.addEventListener("error", function (e) {
		        	_self.render();
		        }, false);
	
		        xhr.upload.addEventListener("abort", function (e) {
		        	_self.render();		            
		        }, false);
		        
		        xhr.onreadystatechange = function () {
		            if (xhr.readyState == 4) {
		                var res = xhr.responseText;	               
		                if(xhr.status >=200 && xhr.status < 300)
		                	_self.appendJS(res);
		                _self.render();
		            }
		        };
	
		        xhr.timeout = 30;
		        xhr.open("GET", url, true);
		        xhr.send(null);
			}
			catch(e)
			{
				_self.bindReady();
			}
		};*/
		
		/**
		 * Add JS code to script tag
		 * @param source
		 */
		/*this.appendJS = function(source)
		{
			if (source != null){
				var oHead = document.getElementsByTagName('HEAD').item(0);
				var oScript = document.createElement( "script" );
				oScript.language = "javascript";
				oScript.type = "text/javascript";				
				oScript.defer = true;
				oScript.text = source;
				oHead.appendChild( oScript );
			}
		};*/
		//End of methods declaration
		
		//this.options=options;
		//Set user defined options 
		for (key in options) 
			if (options.hasOwnProperty(key))
				this.options[key] = options[key];
		
		this.options.url = this.getAbsoluteUrl(this.options.url);
		//Reinit array of uploaders with all available objects 
		this.addAllUploaders();
		/* Read parameter with list of needed uploaders
		 * Comma separated list  
		*/
		if (this.options.uploaders) {
			upls = this.options.uploaders.toLowerCase().trim();
			upls = this.options.uploaders.toLowerCase().split(',');
			tempArr = [];
			for (i = 0; i < upls.length; i++)			
				if(this.uploaders[upls[i].trim()])
				{
					upl = this.uploaders[upls[i].trim()];
					upl.init(this.options);
					if(upl.available)
					{
						tempArr[upl.type]=upl;
						if(this.options.singleUploader)
							break;
					}
				}
			this.uploaders = tempArr;			
		};		
		//alert('init method called');
		//var langUrl = this.initLanguage();
		/*if(langUrl)
			this.getLanguage(langUrl);
		else*/
			//Add dom elements to page when document is ready
		this.bindReady();
	},	
	
	
	/**
	 * Set selected tab
	 * @param tabName - name of tab
	 * @param selected - selected or not
	 */
	setSelectedTab: function(tabName, selected){
		try
		{
			tabHeader = document.getElementById(tabName+"_header");			
			tabHeader.className = selected ? "selected" : "";
			tabBody = document.getElementById(tabName+"_body");			
			tabBody.className = selected ? "selected" :"";
		}
		catch(e){alert(e);}
	},
	/**
	 * Binds specified callback method to the element's event
	 * @param type - event name to bind
	 * @param elem - element to listed
	 * @param cb - callback function
	 */
	addEventListener : function(type, elem, cb) {
		if ( document.addEventListener )
				elem.addEventListener(type, cb, false );		
		else if ( document.attachEvent ) 
				elem.attachEvent( 'on' + type, cb);//function() { return cb.call(elem, window.event);} );			
	},
	/**
	 * Remove (all of ? ) event listener from specified object
	 */
	removeEventListener : function(type, elem, cb) {
		if(elem)
			if( elem.removeEventListener ) 
				elem.removeEventListener( type, cb, false );		
			if( elem.detachEvent ) 
				elem.detachEvent( "on" + type, cb );
	},
	/**
	 * Fire specified event on element
	 */
	fireEvent: function (el, etype){
	    if (el.fireEvent) {
	      el.fireEvent('on' + etype);
	    } else {
	      var evObj = document.createEvent('Events');
	      evObj.initEvent(etype, true, false);
	      el.dispatchEvent(evObj);
	    }
	},

	/**
	 * Add class to specified lement
	 * @param elem - element add class to
	 * @param value - class name 
	 */
	addClass: function(elem, value ) {		
		//if class not set yet, simply set pur new value
		if ( !elem.className ) {
			elem.className = value;
		} else {
			
			var className = " " + elem.className + " ",
				setClass = elem.className;
				if ( className.indexOf( " " + value + " " ) < 0 ) 
					setClass += " " + value;
			elem.className = setClass.trim();
		}
	},

	/**
	 * Remove class from element
	 */
	removeClass: function(elem, value ) {
		
		if ( elem.className ) {
			if ( value ) {
				var className = (" "+elem.className+" ").replace(" "+value+" ", " ");				
				elem.className = className.trim();
			} else 
				elem.className = "";			
		}	
	},
	
	/**
	 * From JQuery code to properly detect document load in all browsers
	 */
	bindReady: function (){
	    if ( this.readyBound ) return;
	    this.readyBound = true;
	    //alert('bindReady');
	    // Mozilla, Opera and webkit nightlies currently support this event
	    if ( document.addEventListener ) {
	        // Use the handy event callback
	        document.addEventListener( "DOMContentLoaded", function(){
	                document.removeEventListener( "DOMContentLoaded", arguments.callee, false );
	                universalUploader.render();
	        }, false );

	    // If IE event model is used
	    } else if ( document.attachEvent ) {
	        // ensure firing before onload,
	        // maybe late but safe also for iframes
	        document.attachEvent("onreadystatechange", function(){
	                if ( document.readyState === "complete" ) {
	                        document.detachEvent( "onreadystatechange", arguments.callee );
	                        universalUploader.render();
	                }
	        });
	        
	        // If IE and not an iframe
	        // continually check to see if the document is ready
	        if ( document.documentElement.doScroll && window == window.top ) (function(){
	                if ( universalUploader.isReady ) return;

	                try {
	                        // If IE is used, use the trick by Diego Perini
	                        // http://javascript.nwbox.com/IEContentLoaded/
	                        document.documentElement.doScroll("left");
	                } catch( error ) {
	                        setTimeout( arguments.callee, 0 );
	                        return;
	                }

	                // and execute any waiting functions
	                universalUploader.render();
	        })();
	    }

	    // A fallback to window.onload, that will always work
	    this.addEventListener("load", window, universalUploader.render );
	},
	/**
	 * Convert relative url to absolute
	 * @param url - any url
	 * @returns absolute url based on current page path
	 */
	getAbsoluteUrl : function(url) {							
		try {
			var path = "", indexSlash = -1;
			if (url.match(/^https?:\/\//i) || url.match(/^\//) || url === "") {
				return url;
			}			
			indexSlash = window.location.pathname.lastIndexOf("/");
			path = (indexSlash <= 0) ? "/" : window.location.pathname.substr(0, indexSlash) + "/";
			return path + url;
		} catch (ex) {
			return url;
		}
	},
	/**
	 * generates "unique" identifier
	 */
	getUid: function(){
		return ((new Date()).getTime()+""+Math.floor(Math.random()*1000000)).substr(0, 18); 
	},
	
	
	/**
	 * Code from 
	 * http://phpjs.org/functions/number_format:481
	 * Licensed under MIT License
	 * Change default separator for thousands to empty string 
	 */
	formatNumber : function(number, decimals, dec_point, thousands_sep) {	  
	    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
	    var n = !isFinite(+number) ? 0 : +number,
	        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),        sep = (typeof thousands_sep === 'undefined') ? '' : thousands_sep,
	        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
	        s = '',
	        toFixedFix = function (n, prec) {
	            var k = Math.pow(10, prec);            return '' + Math.round(n * k) / k;
	        };
	    // Fix for IE parseFloat(0.55).toFixed(0) = 0;
	    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
	    if (s[0].length > 3) {        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
	    }
	    if ((s[1] || '').length < prec) {
	        s[1] = s[1] || '';
	        s[1] += new Array(prec - s[1].length + 1).join('0');    }
	    return s.join(dec);
	},
	
	getFormFields : function (formName) 
	{ 
		var FormObj = document.getElementById(formName), retArr = new Array(),	
		nvp = new Array(), counter=0, sm = false;
		if(FormObj)
		for (var i = 0; i<FormObj.elements.length; i++)
		{		  
			nvp = new Array();		  
			nvp[0]=FormObj.elements[i].name;	
			sm = false;	  
			if(FormObj.elements[i].type == "checkbox" || FormObj.elements[i].type == "radio")		  
			{			  
				if(FormObj.elements[i].checked)
					nvp[1]=FormObj.elements[i].value;
				else			 
					nvp=null;		  
			} 		  
			else 			
				if(FormObj.elements[i].type == "select-multiple")			
				{				
					var selectMultiple = FormObj.elements[i];				
					for (var j = 0; j < selectMultiple.length; j++) 
					{ 					
						if (selectMultiple.options[j].selected) 
						{ 	
							nvp = new Array();	
							nvp[0]=FormObj.elements[i].name; 
							nvp[1]= selectMultiple.options[j].value; 				
							retArr[counter] = nvp;		  						
							counter++;					
							sm = true;
						} 				
					} 					
				}			
				else			  
					nvp[1]=FormObj.elements[i].value; 			 		 
			if(!sm)
			{ 
				retArr[counter] = nvp;
				counter++;		
			}
		} 		
		return retArr;
	},
	
	applyTranslation : function(language){
		for (key in language) 
			if (language.hasOwnProperty(key))
				this.translation[key] = language[key];
		//this.render(true);
	},
	
	initLanguage: function(){
		var lCode = "en";
		if(this.options.language_autoDetect)
			lCode = (navigator.language) ? navigator.language : navigator.userLanguage;
		else
			if(this.options.language_source && this.options.language_source.indexOf("{0}") >= 0)		
				//do not load language file if  autoDetect disabled and value of source contain PLACEHOLDER for language code				
				return null;
		if(lCode == undefined)
			lCode = "en";
		else
			if(lCode.length > 1)
				lCode = lCode.substr(0,2);
		
		lCode = lCode.toLowerCase();	
		var langFileUrl  = this.options.language_source.replace(/\{0\}/g, lCode); 
		//load language xml file
		if(langFileUrl)
			this.loadJsFile(langFileUrl);
		return null;
	},
		
	loadJsFile : function (filename){		
		 var fileref=document.createElement('script');
		 fileref.setAttribute("type","text/javascript");
		 fileref.setAttribute("src", filename);
		 
		 if(fileref)
			 document.getElementsByTagName("head")[0].appendChild(fileref);
	},

	zeroPad : function (a,b) {
		return (a.toString().length>b)?a:('00000000000000000000'+a).slice(-b);
	}, 
	
	getVersion: function(){
		return "1.0 RC";
	}
};

/**
 * File object provide basic information about file
 * @param fid - unique identificator for file
 * @param fileName - file name 
 * @param fileSize - file size . not available in html4 mode
 */
universalUploader.File = function(fid, fileName, fileSize) {	
	var _self = this;
	//Some browsers provide full file path. So we need to extract only file name
	//Replace \ character with / 
	fileName = fileName.replace(/\\/g, '/');
	//And get only file name .   
	if(fileName.lastIndexOf('/') > 0)
		fileName = fileName.substring(fileName.length, fileName.lastIndexOf('/') + 1);
	this.id = fid;
	/**
	 * File status
	 * 0 - ready
	 * 1 - in process
	 * 2 - complete
	 * 3 - stopped
	 * 4 - error 
	 */
	this.status = 0;	
	this.name = fileName;	
	this.size = !isNaN(fileSize) && fileSize >0 ? fileSize : -1;	
	this.bytesLoaded = 0;
	this.serverResponse = "";
	this.getPercent = function(){
		var perc = Math.round((_self.bytesLoaded/_self.size)*100);
		//return 100% only when file completely uploaded
		return (_self.status == 2 ) ? 100: (perc >= 100) ? 99 : perc; 
	};
};
/**
 * ProgressInfo object provides information about upload process
 */
universalUploader.ProgressInfo = function(){
	var _self = this;
	_self.lastError = "";
	_self.totalFiles = 0;	
	_self.totalSize = 0;
	_self.uploadedFiles = 0;
	_self.uploadedSize = 0;
	_self.startTime = new Date();
	_self.stopTime = new Date();
	_self.lastProgressTime = new Date();
	_self.lastBytes = 0;
	_self.bandwidth = 0;
	_self.avgBw = [];
	_self.avBwCount = 30;
	_self.avBwCurr = 0;
	_self.getPercent = function(){
		return (_self.totalSize == 0 ) ? 0 : (_self.uploadedSize/_self.totalSize)*100;
	};
	_self.reset = function(){
		_self.resetStat();
		_self.resetProgress();
	};
	
	_self.resetStat = function(){
		var fileStat = universalUploader.getFileStat();
		_self.totalFiles = fileStat[0];
		_self.totalSize = fileStat[1];
		_self.uploadedFiles = fileStat[2];		
		_self.uploadedSize = fileStat[3];		
	};
	
	_self.resetProgress = function(){		
		_self.startTime = new Date();
		_self.stopTime = new Date();
		_self.lastProgressTime = new Date();
		_self.lastBwStore = new Date();
		_self.lastBytes = 0;
		_self.bandwidth = 0;
		_self.avgBw = [];
		_self.avBwCurr = 0;
	};
	
	_self.getBandwdth = function(){
		var bw =0;
		for(i = 0; i < _self.avgBw.length; i++)
			bw+=_self.avgBw[i];
		return _self.avgBw.length > 0 ?  bw/_self.avgBw.length : universalUploader.getTranslatedString("constant_notAvailable"); 
	};
	
	_self.onProgress = function(bytesUploaded){
		var currentDate = new Date();
		_self.stopTime = currentDate;
		/* milliseconds from last progress event */
		var timedif = currentDate.getTime()-_self.lastProgressTime.getTime();
		/* bytes uploaded since last progress event*/
		var chunk = bytesUploaded - _self.lastBytes;
		if(chunk >0)
		{
			/* Increase total uploaded bytes*/
			_self.uploadedSize += chunk;
							
			if(timedif<=0)  /*defence from devision by zero*/
				timedif = 1;
			
			/* Calculate current bandwidth */
			_self.bandwidth = (chunk/(timedif/1000));
			if(currentDate.getTime()-_self.lastBwStore.getTime() > 500 || _self.avgBw.length == 0)
			{
				if(_self.avBwCurr < _self.avBwCount)
					_self.avgBw.push(_self.bandwidth);
				else
					_self.avgBw[_self.avBwCur]=_self.bandwidth;
				_self.avBwCurr++;
				_self.lastBwStore = currentDate;
			}
			/* Store current time and uploaded bytes*/
			_self.lastProgressTime = currentDate;			
			_self.lastBytes = bytesUploaded;
		}
	};
	
	_self.getTotalPercent = function(){
		return _self.totalSize > 0 ? Math.round((_self.uploadedSize/_self.totalSize)*100) : _self.uploadedFiles > 0 ? Math.round(_self.uploadedFiles/_self.totalFiles*100): 0;		 
	};
	
	//get time left in seconds based on current average bandwidth
	_self.timeLeft = function()
	{
		if(_self.bandwidth >0)
		{
			var leftSec = (_self.totalSize-_self.uploadedSize)/(_self.getBandwdth());
			return leftSec > 0 ? leftSec : 0;
		}
		return 0;
	};	
	//hours part of time left
	_self.timeLeftHour = function()
	{
		return Math.floor(_self.timeLeft()/60/60);			
	};
	//mins part of time left
	_self.timeLeftMin = function()
	{			
		return Math.floor(_self.timeLeft()/60-Math.floor(_self.timeLeftHour())*60);			
	};
	//secs part of time left
	_self.timeLeftSec = function()
	{
		return Math.round(_self.timeLeft()%60);//-Math.floor(timeLeftMin)*60-Math.floor(timeLeftHour)*60;		
	};
	
	_self.elapsedTime = function()
	{			
		return (_self.stopTime.getTime()-_self.startTime.getTime())/1000;			
	};
	
	//hours part of time left
	_self.elapsedHour = function()
	{
		return Math.floor(_self.elapsedTime()/60/60);			
	};
	//mins part of time left
	_self.elapsedMin = function()
	{			
		return Math.floor(_self.elapsedTime()/60-Math.floor(_self.elapsedHour())*60);			
	};
	//secs part of time left
	_self.elapsedSec = function()
	{
		return Math.round(_self.elapsedTime()%60);// -Math.floor(elapsedMin)*60);		
	};
		

	/**
	 * Replace all known placeholders and return combined string
	 * @param pattern - string with placeholders
	 * 0 - percent
	 * 1 - total files count
	 * 2 - total files size
	 * 3 - uploaded files count
	 * 4 - uploaded files size
	 * 5 - bandwidth
	 * 6 - hours left
	 * 7 - minutes left
	 * 8 - seconds left
	 * 
	 * 12 - error message
	 */
	_self.replacePlaceHolders = function(pattern){
		var ret = pattern.replace(/\{0\}/g, _self.getTotalPercent());
		ret = ret.replace(/\{1\}/g, _self.totalFiles);
		ret = ret.replace(/\{2\}/g, universalUploader.formatBytes(_self.totalSize));
		ret = ret.replace(/\{3\}/g, _self.uploadedFiles);
		ret = ret.replace(/\{4\}/g, universalUploader.formatBytes(_self.uploadedSize));
		ret = ret.replace(/\{5\}/g, universalUploader.formatBytes(_self.getBandwdth()));
		ret = ret.replace(/\{6\}/g, universalUploader.zeroPad(_self.timeLeftHour(), 2));
		ret = ret.replace(/\{7\}/g, universalUploader.zeroPad(_self.timeLeftMin(), 2));
		ret = ret.replace(/\{8\}/g, universalUploader.zeroPad(_self.timeLeftSec(), 2));
		ret = ret.replace(/\{9\}/g, universalUploader.zeroPad(_self.elapsedHour(), 2));
		ret = ret.replace(/\{10\}/g, universalUploader.zeroPad(_self.elapsedMin(), 2));
		ret = ret.replace(/\{11\}/g, universalUploader.zeroPad(_self.elapsedSec(), 2));
		ret = ret.replace(/\{12\}/g, _self.lastError);
		return ret;
	};
};

/**
 * Flash uploader implementation
 * should detect flash player version
 */
universalUploader.Flash = {
		type: 'flash',
		available: true,
		options: {},
		params: {},
		inited : false,
		/**
		 * Method taken from swfobject project 
		 * http://code.google.com/p/swfobject/
		 * @returns major fp version
		 */
		detectFlashPlayer: function(){
			 var playerVersion = [0,0,0],
             d = null;
		     if (typeof navigator.plugins != "undefined" && typeof navigator.plugins["Shockwave Flash"] == "object") {
		             d = navigator.plugins["Shockwave Flash"].description;
		             if (d && !(typeof navigator.mimeTypes != "undefined" && navigator.mimeTypes["application/x-shockwave-flash"] && !navigator.mimeTypes["application/x-shockwave-flash"].enabledPlugin)) { // navigatorigator.mimeTypes["application/x-shockwave-flash"].enabledPlugin indicates whether plug-ins are enabled or disabled in Safari 3+
		                     plugin = true;
		                     ie = false; // cascaded feature detection for Internet Explorer
		                     d = d.replace(/^.*\s+(\S+\s+\S+$)/, "$1");
		                     playerVersion[0] = parseInt(d.replace(/^(.*)\..*$/, "$1"), 10);
		                     playerVersion[1] = parseInt(d.replace(/^.*\.(.*)\s.*$/, "$1"), 10);
		                     playerVersion[2] = /[a-zA-Z]/.test(d) ? parseInt(d.replace(/^.*[a-zA-Z]+(.*)$/, "$1"), 10) : 0;
		             }
		     }
		     else if (typeof window.ActiveXObject != "undefined") {
		             try {
		                     var a = new ActiveXObject("ShockwaveFlash.ShockwaveFlash");
		                     if (a) { // a will return null when ActiveX is disabled
		                             d = a.GetVariable("$version");
		                             if (d) {
		                                     ie = true; // cascaded feature detection for Internet Explorer
		                                     d = d.split(" ")[1].split(",");
		                                     playerVersion = [parseInt(d[0], 10), parseInt(d[1], 10), parseInt(d[2], 10)];
		                             }
		                     }
		             }
		             catch(e) {}
		     }
		     return playerVersion[0];
		},
		
		/*
		 * Initialize flash based uploader
		 */
		init: function(options)
		{		
			var _self = this;
			_self.currentState = universalUploader.FILE_READY;
			this._files = [];
			this.progressInfo = new universalUploader.ProgressInfo();
			this.options.renderFlashUi = options.flash_ownUi;
			this.options.swfUrl = options.flash_swfUrl ? options.flash_swfUrl : "ElementITMultiPowUpload.swf";
			this.params.serialNumber = options.serialNumber;
			if(!this.options.renderFlashUi)
			{
				this.params.hiddenMode = true;
				this.params.overlayObjectId = "browseButton_"+this.type;
			}
			if(options.postFields)
			{
				this.params.customPostFields = "";
				for(key in options.postFields) 
					if (options.postFields.hasOwnProperty(key))
						this.params.customPostFields += key + ";" + options.postFields[key]+"|";
			}	
			/* Uncomment this block to apply and check filters directly in Flash movie
			 * if(options.fileFilter_maxCount)
				this.params["fileFilter.maxCount"] = options.fileFilter_maxCount;
			if(options.fileFilter_maxSize)
				this.params["fileFilter.maxSize"] = options.fileFilter_maxSize;
			if(options.fileFilter_maxTotalSize)
				this.params["fileFilter.maxTotalSize"] = options.fileFilter_maxTotalSize;
			if(options.fileFilter_maxCount)
				this.params["fileFilter.maxCount"] = options.fileFilter_maxCount;
			if(options.fileFilter_disabledTypes)
				this.params["fileFilter.disabledTypes"] = options.fileFilter_disabledTypes.replace(/,/g,";");
			if(options.fileFilter_types)
			{
				var types = options.fileFilter_types.split(",");
				var i=0;
				this.params["fileFilter.types"] = "Allowed files| ";
				for(i=0; i < types.length; i++)
					this.params["fileFilter.types"] += "*."+types[i]+(i==types.length?"":":");				
			}*/
			/*Set post field names, MultiPowUpload will automatically aappend all needed information to the post request*/
			if(options.postFields_file)
				this.params["postFields.file"] = options.postFields_file;			
			if(options.postFields_fileId)
				this.params["postFields.fileId"] = options.postFields_fileId;
			if(options.postFields_fileSize)
				this.params["postFields.fileSize"] = options.postFields_fileSize;
			if(options.postFields_filesCount)
				this.params["postFields.filesCount"] = options.postFields_filesCount;
			
			this.params["debug.enabled"]=false;
			this.params.uploadUrl = encodeURIComponent(options.url);
			this.params.checkConnectionOnIOError = false;
			this.params.showIOError = true;
			this.params.useExternalInterface = "true";
			this.params.javaScriptEventsPrefix = "universalUploader.Flash.MultiPowUpload";
			//
			for (key in options.flash_params) 
				if (options.flash_params.hasOwnProperty(key)) 
					this.params[key] = options.flash_params[key];
			
			//fp 10 required
			if(this.detectFlashPlayer() < 10)
			{
				this.available = false;				
				return false;
			}	
			/**
			 * Build correct value for FlashVars parameter
			 * @returns {String} 
			 */
			this.getFlashVars = function(){
				var flashVars = "";
				for (key in _self.params) 
					if (_self.params.hasOwnProperty(key)) 
						flashVars += key+"="+_self.params[key]+"&";
				return flashVars;
			},
			/**
			 * Render flash uploader
			 * @returns {String} 
			 */
			this.render = function (){				
				var tabContents = '';
				
				if(!_self.options.renderFlashUi)
					tabContents +='<div id="controlsContainer_'+_self.type+'" class="uuControlsContainer">'+
					'<a class="uuButton uuClearButton" href="#" id="clearButton_'+_self.type+'" onclick="javascript: universalUploader.clearList();"><span><span><img src="'+universalUploader.getIcon("clear")+'"/>'+universalUploader.getTranslatedString("button_clear")+'</span></span></a>'+
					'<a id="browseButton_'+_self.type+'" class="uuButton" href="#"><span><span><img src="'+universalUploader.getIcon("add")+'"/>'+universalUploader.getTranslatedString("button_browse")+'</span></span></a>'+
					'&nbsp;<a class="uuButton" href="#" id="uploadButton_'+_self.type+'" onclick="javascript: universalUploader.uploadButtonHandler();"><span><span><img src="'+universalUploader.getIcon("upload")+'"/>'+universalUploader.getTranslatedString("button_upload")+'</span></span></a>'+
					
					
					'</div>';										
					
				var flashContents = '<div id="MultiPowUpload_holder">'+
				'<strong>You need at least 10 version of Flash player!</strong>'+
				'<a href="http://www.adobe.com/go/getflashplayer">&nbsp;Get Adobe Flash player</a>'+
				'</div>	';
		
				if(!_self.options.renderFlashUi)
				{
					tabContents +='<div id="'+_self.type+'_fileList" class="fileList">'+flashContents+'</div>';
				}	
				else
					tabContents += flashContents;
				return tabContents;
			};
			this.isProgressVisible = function(){
				return !_self.options["renderFlashUi"];
			};
					
			this.afterRender = function(){
				if(!_self.inited)
				{
					universalUploader.setButtonState(_self.type, "browseButton_", "add", false);
					var params = {  
						BGColor: "#FFFFFF",
						wmode:(_self.isProgressVisible() ? 'transparent' : 'window')					
					};
					
					var attributes = {  
						id: "MultiPowUpload",  
						name: "MultiPowUpload",
						style: "position: "+(_self.isProgressVisible() ? 'absolute' : 'relative')+";"
					};
					
					//MultiPowUpload partameters goes here
					var flashvars = {		
					  "serialNumber": _self.params["serialNumber"],
					  "useExternalInterface": "true",
					  "uploadUrl": _self.params["url"]
					};
					for (key in _self.params) 
						if (_self.params.hasOwnProperty(key)) 
							flashvars[key] = _self.params[key];
					var tabHolder = document.getElementById(_self.type+'_content');
					var width = tabHolder.offsetWidth-20, height = tabHolder.offsetHeight-20;
					if(!_self.options.renderFlashUi)
					{
						try{
							var lst = document.getElementById(_self.type+'_fileList');
							if(lst)
							{
								width = lst.offsetWidth-3;
								height -= document.getElementById('controlsContainer_'+_self.type).offsetHeight;
								height -= document.getElementById(_self.type+'_statusPanel').offsetHeight;
							}
						}
						catch(error){}
					}
					//Default MultiPowUpload should have minimum width=400 and minimum height=180
					swfobject.embedSWF(_self.options["swfUrl"], "MultiPowUpload_holder", width, height, "10.0.0", null, flashvars, params, attributes);
				}
			};
			
			/**
			 * Start upload process
			 */
			this.upload = function(){
				/**/
				if(options.postFields)
				{
					this.params.customPostFields = "";
					for(key in options.postFields) 
						if (options.postFields.hasOwnProperty(key))
							this.params.customPostFields += key + ";" + options.postFields[key]+"|";
					MultiPowUpload.setParameter("customPostFields", this.params.customPostFields);
				}
				_self.stopped=false;
				MultiPowUpload.uploadAll();
			};
			
			/**
			 * Stop upload process
			 */
			this.stop = function(){
				if(!_self.stopped)
				{
					MultiPowUpload.cancelUpload();
					_self.stopped=true;
				}
			};
			
						
			this.convertFile = function(mpuFile){
				var uuFile = new universalUploader.File(mpuFile.id, mpuFile.name, mpuFile.size);
				uuFile.status = mpuFile.status;
				uuFile.bytesLoaded = mpuFile.percentDone/100*mpuFile.size;
				return uuFile;
			};
			/**
			 * Return current file list
			 */
			this.getFiles = function(){				
				return _self._files;
			};
			
			
			/**
			 * RemoveAll files from list
			 */
			this.clearList = function(){
				MultiPowUpload.removeAll();
			};	
			
			/**
			 * Remove file object by its id
			 */
			this.removeFile = function(fileId){
				MultiPowUpload.removeFile(fileId);
			};	
			
			this._removeFile = function(fileId, doNotCallEventHandler){
				universalUploader.onRemoveFile(_self.type, fileId, doNotCallEventHandler);
				if(_self._files[fileId])
				{
					_self._files.splice(_self._files.indexOf(_self._files[fileId]),1);
					_self._files[fileId] = null;
					delete _self._files[fileId];
				}				
			};
			
			this.MultiPowUpload_onMovieLoad = function(){
				_self.inited = true;
				universalUploader.setButtonState(_self.type, "browseButton_", "add", true);
				//place event listeners on flash movie
				universalUploader.positionFormUnderButton(document.getElementById('browseButton_'+_self.type), null, MultiPowUpload);
				//If we have some files in list , we should remove them here
				//because movie was reinited
				for (var i = _self._files.length-1; i >= 0; i--) 
					_self._removeFile(_self._files[i].id);
				
			};
			//MultiPowUpload_onSelect. Invoked when the user selects a file to upload or download from the file-browsing dialog box.
			this.MultiPowUpload_onAddFiles = function(afiles)
			{
				//Internal files store. It allow us to switch tabs without loosing file objects			
				var addedfiles = [], file = null, tSize=0, invalidFiles = [0,0,0,0,0,0,0,0,0,0,0,0], res =-1, invalidFilesCount =0;
				/* Uncomment this block to add files without filters
				 * for (var i = 0; i < files.length; i++) 
					addedfiles.push(_self.convertFile(files[i]));*/
				
				// JS check for filter conditions BEGIN
				for (var i = 0; i < afiles.length; i++) {
	                var file = afiles[i];
	                if (file.name != "") {	                	
	                    file = _self.convertFile(afiles[i]);
	                    res = universalUploader.isValidFile(file, addedfiles, tSize);
	                    if(res < 0)
	                    {
	                    	addedfiles.push(file);
	                    	tSize += file.size;
	                    }
	                    else
	                    {
	                    	MultiPowUpload.removeFile(file.id);
	                    	invalidFiles[res]++;
	                    	invalidFilesCount ++;
	                    }
	                }
	            }
			 
			
				if(invalidFilesCount > 0)
					universalUploader.displayResultOfAdd(invalidFiles);
				// JS check for filter conditions END
				
				//Internal files store. It allow us to switch tabs without loosing file objects
				for (var i = 0; i < addedfiles.length; i++) {
					_self._files[addedfiles[i].id] = addedfiles[i];
					_self._files.push(addedfiles[i]);
				}
							
				if(addedfiles.length > 0)
					 universalUploader.onAddFiles(_self.type, addedfiles);
			};
			
			this.MultiPowUpload_onRemoveFiles = function(files)
			{
				for (var i = 0; i < files.length; i++) 
					_self._removeFile(files[i].id);
			};
			
			this.MultiPowUpload_onClearList = function(files)
			{		
				universalUploader.onClearList(_self.type);
				for (var i = _self._files.length-1; i >= 0; i--)
					_self._removeFile(_self._files[i].id, true);
			};
			
			this.MultiPowUpload_onFileStart = function(file)
			{
				universalUploader.onFileUploadStart(_self.type, file.id);
			};
			
			//MultiPowUpload_onProgress. Invoked periodically during the file upload or download operation
			this.MultiPowUpload_onProgress = function(file) 
			{
				universalUploader.onFileUploadProgress(_self.type, file.id, MultiPowUpload.getProgressInfo().currentFileUploadedBytes);
			};
			
			this.MultiPowUpload_onCancel = function()
			{
				_self.stopped = true;
				universalUploader.onFileUploadStop(_self.type, MultiPowUpload.getProgressInfo().currentListItem.id);
			};
			
			//MultiPowUpload_onError. Invoked when an input/output error occurs or when an upload/download fails because of an HTTP error
			this.MultiPowUpload_onError = function(file, error) 
			{
				universalUploader.onFileUploadError(_self.type, file.id, "", error);
				_self.MultiPowUpload_onCancel();
			};
			
			this.MultiPowUpload_onErrorMessage = function(error) 
			{
				universalUploader.onError(_self.type, error);
			};
			
			this.MultiPowUpload_onStart = function()
			{		
				universalUploader.onUploadStart(_self.type);		
			};

			//MultiPowUpload_onCompleteAbsolute. Invoked when the upload or download of all files operation has successfully completed
			this.MultiPowUpload_onComplete = function()
			{
				universalUploader.onUploadComplete(_self.type);
			};
			
			this.MultiPowUpload_onServerResponse = function(file)
			{	
				universalUploader.onFileUploadComplete(_self.type, file.id, file.serverResponse);
			};
		}
				
};

/**
 * UniversalUploader implementation
 * 
 */
universalUploader.Silverlight = {
		type: 'silverlight',
		available: true,
		options: {},
		params: {},
		uploadController: null,
		inited: false,
		/**
		 * Method taken from swfobject project 
		 * http://code.google.com/p/swfobject/
		 * @returns major fp version
		 */
		detectSilverlightVersion: function(vers){
			var SLVersion;
			try {  
			       try {
			            var control = new ActiveXObject('AgControl.AgControl');
			            if (control.IsVersionSupported(vers))                
			               SLVersion = Number(vers);
			            else			                           
			               SLVersion = Number(vers)-1;			            
			      }
			      catch (e) {      
	                     var plugin = navigator.plugins["Silverlight Plug-In"];
	                     if (plugin)
	                     { 
	                       if (plugin.description === "1.0.30226.2")             
	                          SLVersion = 2;
	                       else
	                          SLVersion = parseInt(plugin.description[0]);
	                     }
	                     else
	                         SLVersion = 0;
			      }
			}
			catch (e) { 
			      SLVersion = 0;
			}
			return SLVersion;
		},
		
		/*
		 * Initialize flash based uploader
		 */
		init: function(options)
		{		
			var _self = this;
			_self.currentState = universalUploader.FILE_READY;
			this._files = [];
			this.options = options;
			this.progressInfo = new universalUploader.ProgressInfo();
			this.options.renderOwnUi = options.silverlight_ownUi;
			this.options.xapUrl = options.silverlight_xapUrl ? options.silverlight_xapUrl : "UltimateUploader.xap";
			this.params.LicenseKey = options.serialNumber;
			this.params.HandlersObject = "universalUploader.Silverlight";
			this.params.ChunkMultipart = true;
			if(!this.options.renderOwnUi)			
				this.params.HiddenMode = true;			
			
			if(options.postFields)
			{
				this.params.CustomPostFields = "";
				for(key in options.postFields) 
					if (options.postFields.hasOwnProperty(key))
						this.params.CustomPostFields += key + ";" + options.postFields[key]+"|";
			}
			
			/*Set post field names, MultiPowUpload will automatically aappend all needed information to the post request*/
			if(options.postFields_file)
				this.params["PostFieldsFile"] = options.postFields_file;			
			if(options.postFields_fileId)
				this.params["PostFieldsFileId"] = options.postFields_fileId;
			if(options.postFields_fileSize)
				this.params["PostFieldsFileSize"] = options.postFields_fileSize;
			if(options.postFields_filesCount)
				this.params["PostFieldsFilesCount"] = options.postFields_filesCount;
						
			this.params.UploadHandler = options.url +(options.url.indexOf("?") >=0 ? "&" : "?")+"chunkedUpload=true";
		
			//this.params.javaScriptEventsPrefix = "universalUploader.Flash.MultiPowUpload";
			//
			/*for (key in options.silverlight_params) 
				if (options.silverlight_params.hasOwnProperty(key)) 
					this.params[key] = options.silverlight_params[key];
			*/
			//fp 10 required
			if(this.detectSilverlightVersion("4.0") < 4)
			{
				this.available = false;				
				return false;
			}	
			/**
			 * Build correct value for FlashVars parameter
			 * @returns {String} 
			 */
			this.getInitParams = function(){
				var initParams = "";
				for (key in _self.params) 
					if (_self.params.hasOwnProperty(key)) 
						initParams += key+"="+_self.params[key]+",";
				return initParams;
			},
			/**
			 * Render flash uploader
			 * @returns {String} 
			 */
			this.render = function (){				
				var tabContents = '';
				if(!_self.options.renderOwnUi)
					tabContents +='<div id="controlsContainer_'+_self.type+'" class="uuControlsContainer">'+
					'<a class="uuButton uuClearButton" href="#" id="clearButton_'+_self.type+'" onclick="javascript: universalUploader.clearList();"><span><span><img src="'+universalUploader.getIcon("clear")+'"/>'+universalUploader.getTranslatedString("button_clear")+'</span></span></a>'+
					'<a id="browseButton_'+_self.type+'" class="uuButton" href="#"><span><span><img src="'+universalUploader.getIcon("add")+'"/>'+universalUploader.getTranslatedString("button_browse")+'</span></span></a>'+
					'&nbsp;<a class="uuButton" href="#" id="uploadButton_'+_self.type+'" onclick="javascript: universalUploader.uploadButtonHandler();"><span><span><img src="'+universalUploader.getIcon("upload")+'"/>'+universalUploader.getTranslatedString("button_upload")+'</span></span></a>'+									
					'</div>';
				if(!_self.options.renderOwnUi)				
					tabContents +='<div id="'+_self.type+'_fileList" class="fileList"></div>';
				//tabContents +=silverlightHolder;
				return tabContents;
			};
			
			this.isProgressVisible = function(){
				return !_self.options.renderOwnUi;
			};
					
			this.afterRender = function(){				
				if(!_self.inited)	
				{
					_self.inited = true;
					universalUploader.setButtonState(_self.type, "browseButton_", "add", false);
					//document.getElementById("ultimateUploader").style.display = "block";
					var tabHolder = document.getElementById(_self.type+'_content');
					var width = tabHolder.offsetWidth-20, height = tabHolder.offsetHeight-20;
										
					var silverlightHolder = document.createElement('div');
					silverlightHolder.id = "silverlightHolder_"+_self.type;
					
					if(!_self.options.renderOwnUi)
					{
						silverlightHolder.style.position = "absolute";						
						silverlightHolder.style.top = "0px";
						silverlightHolder.style.zIndex = "99999";						
					}
					try{
						silverlightHolder.style.width = width+"px";
						silverlightHolder.style.height = height+"px";
					}
					catch(e){}
					if(document.getElementById(_self.type+"_content"))
						document.getElementById(_self.type+"_content").appendChild(silverlightHolder);
					//
					silverlightHolder.innerHTML='<object id="ultimateUploader" data="data:application/x-silverlight-2," type="application/x-silverlight-2"'+
	                ' width="100%" height="100%" >'+
	                '<param name="source" value="'+_self.options["xapUrl"]+'" />'+
	                 //'<param name="background" value="white" />'+
					
					'<param name="windowless" value="'+(!_self.options.renderOwnUi? "true": "false")+'"/>'+
					'<param name="minRuntimeVersion" value="4.0.50826.0" />'+
					(!_self.options.renderOwnUi ? '<param name="background" value="Transparent"/>' :'') +     				 
	 				'<param name="enablehtmlaccess" value="true"/>' +
	                '<param name="autoUpgrade" value="true" />'+
	              
	                '<param name="initParams" value="'+_self.getInitParams()+'" />'+                     
	                '</object>';
				}
				
			};
			
			/**
			 * Start upload process
			 */
			this.upload = function(){
				if(options.postFields)
				{
					this.params.CustomPostFields = "";
					for(key in options.postFields) 
						if (options.postFields.hasOwnProperty(key))
							this.params.CustomPostFields += key + ";" + options.postFields[key]+"|";
					uploadController.CustomPostFields = this.params.CustomPostFields;
				}
				if(_self.uploadController) 
				{
					this.stopped = false;
					uploadController.StartUpload();
				}
			};
			
			/**
			 * Stop upload process
			 */
			this.stop = function(){
				if(!_self.stopped)
				{
					if(_self.uploadController) 
						uploadController.CancelUpload();
					_self.stopped=true;
				}
			};
			
						
			this.convertFile = function(ultimateFile){
				var uuFile = new universalUploader.File(ultimateFile.Id, ultimateFile.FileName, ultimateFile.FileLength);
				uuFile.status = ultimateFile.Status;
				switch(ultimateFile.Status)
				{
					case 0: 
						uuFile.status = 0;
						break;
					case 1: 
						uuFile.status = 1;
						break;
					case 2: 
						uuFile.status = 0;
						break;
					case 3: 
						uuFile.status = 2;
						break;
					case 4: 
						uuFile.status = 4;
						break;
					case 5: 
						uuFile.status = 5;
						break;					
				}
				/* 0-Pending, - 0
        			1-Uploading, - 1
        			2-Preparing,
        			3-Complete, - 2
        			4-Error, - 4
        			5-Stopped, - 5
        			6-Removed
				 */
				uuFile.bytesLoaded = ultimateFile.UploadPercent/100*ultimateFile.FileLength;
				return uuFile;
			};
			/**
			 * Return current file list
			 */
			this.getFiles = function(){				
				return _self._files;
			};
			
			
			/**
			 * RemoveAll files from list
			 */
			this.clearList = function(){
				if(_self.uploadController)					
					uploadController.RemoveAll();
			};	
			
			/**
			 * Remove file object by its id
			 */
			this.removeFile = function(fileId){
				if(_self.uploadController)
					uploadController.RemoveFile(fileId);
			};	
			
			this._removeFile = function(fileId, doNotCallEventHandler){
				universalUploader.onRemoveFile(_self.type, fileId, doNotCallEventHandler);
				if(_self._files[fileId])
				{
					_self._files.splice(_self._files.indexOf(_self._files[fileId]),1);
					_self._files[fileId] = null;
					delete _self._files[fileId];
				}				
			};
		},
		
		onFileUploadStart : function(event)
		{
			universalUploader.onFileUploadStart(this.type, event.Id);
		},
		
		onFileUploadResume : function(event)
		{
			universalUploader.onFileUploadStart(this.type, event.Id);
		},
		
		//Invoked periodically during the file uploadoperation
		onFileUploadProgress : function(event) 
		{
			universalUploader.onFileUploadProgress(this.type, event.Id, event.BytesUploaded);
		},
				
		onFileUploadCancel : function(event) 
		{
			this.stopped = true;
			universalUploader.onFileUploadStop(this.type, event.Id);
		},
		
		onUploadCancel : function()
		{
			this.stopped = true;
			//universalUploader.onUploadStop(this.type);
		},
				
		onError : function(event) 
		{		
			universalUploader.onFileUploadError(this.type, event.Id, "", event.ErrorMessage);
			
		},
		
		
		onUploadStart : function()
		{		
			this.stopped = false;
			universalUploader.onUploadStart(this.type);		
		},

		//. Invoked when the upload of all files operation has completed
		onUploadComplete : function()
		{
			universalUploader.onUploadComplete(this.type);
		},
		
		onFileUploadComplete : function(event)
		{	
			var resp = event.ServerResponse;
			/*var afiles = uploadController.GetFiles();			
			for (var i = 0; i < afiles.length; i++) {
                var file = afiles[i];
                if (file.FileName != "" && file.Id == event.Id && !file.ActuallyUploaded)
                {
                	resp = "File skipped";
                }
			}*/
			universalUploader.onFileUploadComplete(this.type, event.Id, resp);
		},
		
		onRemoveFiles : function(event)
		{
			var files = event.Files;
			for (var i = 0; i < files.length; i++) 
				this._removeFile(files[i].Id);
		},
		
		onClearList : function(files)
		{		
			universalUploader.onClearList(this.type);
			for (var i = this._files.length-1; i >= 0; i--)
				this._removeFile(this._files[i].id, true);
		},
		
		//Invoked when the user selects a file to upload 
		onAddFiles : function(event)
		{
			var afiles = event.Files;			
			//Internal files store. It allow us to switch tabs without loosing file objects			
			var addedfiles = [], file = null, tSize=0, invalidFiles = [0,0,0,0,0,0,0,0,0,0,0,0], res =-1, invalidFilesCount =0;
			/* Uncomment this block to add files without filters
			 * for (var i = 0; i < files.length; i++) 
				addedfiles.push(_self.convertFile(files[i]));*/
			
			// JS check for filter conditions BEGIN
			for (var i = 0; i < afiles.length; i++) {
                var file = afiles[i];
                if (file.FileName != "") {	                	
                    file = this.convertFile(afiles[i]);
                    res = universalUploader.isValidFile(file, addedfiles, tSize);
                    if(res < 0)
                    {
                    	addedfiles.push(file);
                    	tSize += file.size;
                    }
                    else
                    {
                    	//MultiPowUpload.removeFile(file.id);
                    	invalidFiles[res]++;
                    	invalidFilesCount ++;
                    }
                }
            }
		 
		
			if(invalidFilesCount > 0)
				universalUploader.displayResultOfAdd(invalidFiles);
			// JS check for filter conditions END
			
			//Internal files store. It allow us to switch tabs without loosing file objects
			for (var i = 0; i < addedfiles.length; i++) {
				this._files[addedfiles[i].id] = addedfiles[i];
				this._files.push(addedfiles[i]);
			}
						
			if(addedfiles.length > 0)
				 universalUploader.onAddFiles(this.type, addedfiles);
		},
		/**
		 * UltimatUploader inited and ready to work
		 */
		onInit: function(){
			this.inited = true;
			
			this.uploadController = document.getElementById("ultimateUploader").Content.JSAPI;
			universalUploader.setButtonState(this.type, "browseButton_", "add", true);
			//place event listeners on flash movie
			var silverlight = document.getElementById("silverlightHolder_"+this.type);
			// var movie = document.getElementById("MultiPowUpload");
			var coverableObject = document.getElementById("browseButton_"+this.type);
			if(silverlight != null && coverableObject != null)
			{
				/*silverlight.style.opacity = "0.01";
				silverlight.style.overflow = "hidden";
				silverlight.style.background = "transparent";*/
				
				 if(!silverlight.style.position || silverlight.style.position != 'absolute')
					 silverlight.style.position = 'absolute';
				 silverlight.style.width = coverableObject.offsetWidth+'px';
				 silverlight.style.height = coverableObject.offsetHeight+'px';
				 
				 var topCoordinate = 0;
				 var leftCoordinate = 0;
				 var obj = coverableObject;
				 while(obj && obj.tagName != "BODY" && silverlight.offsetParent!=obj && obj.nodeType)
				 {									 
					  topCoordinate += obj.offsetTop || 0;
					  leftCoordinate += obj.offsetLeft || 0;
					  obj = obj.offsetParent;
				 }     
				obj = coverableObject.parentNode;
				while (obj && obj.tagName != "BODY" && silverlight.offsetParent!=obj && obj.nodeType) {
					leftCoordinate -= obj.scrollLeft || 0;
					topCoordinate -= obj.scrollTop || 0;
					obj = obj.parentNode;
				}
					
				 silverlight.style.top = topCoordinate+'px';
				 silverlight.style.left = leftCoordinate+'px';
				 universalUploader.positionFormUnderButton(coverableObject, null, document.getElementById("ultimateUploader"));
			}
			
			
			uploadController = document.getElementById("ultimateUploader").Content.JSAPI;	
		    
			//If we have some files in list , we should remove them here
			//because movie was reinited
			//for (var i = universalUploader.Silverlight._files.length-1; i >= 0; i--) 
			//	universalUploader.Silverlight._removeFile(_self._files[i].id);
		}
};

universalUploader.Html4 = {
		type:'classic',
		available: true,
		stopped: false,
		/**
		 * Array of unique identifiers whis is used in inpout and form ids
		 */
		ids:[], 
		/**
		 * Initialize all required parameters
		 * @param options
		 */
		init: function(options)
		{
			var _self = this;
			_self.currentState = universalUploader.FILE_READY;
			this.options = options;
			this.progressInfo = new universalUploader.ProgressInfo();
			this.id="html4_uploadForm";
			this.url = options.url;
			//internal files store 
			this._files = [];
			this.postFields = options.postFields ? options.postFields : {}; 
			
	        /**
			 * Render upload form
			 * @returns {String}
			 */
			this.render= function (uploader){
				var id = universalUploader.getUid();
				_self.lastId = id;
				var initialForm = '<div id="controlsContainer_'+_self.type+'" class="uuControlsContainer"><iframe id="hidden_iframe" name="hidden_iframe" src="#" style="width:0;height:0;border:0px solid #fff;display:none"></iframe>'+
					'<a class="uuButton uuClearButton" href="#" id="clearButton_'+_self.type+'" onclick="javascript: universalUploader.clearList();"><span><span><img src="'+universalUploader.getIcon("clear")+'"/>'+universalUploader.getTranslatedString("button_clear")+'</span></span></a>'+
					'<a id="browseButton_'+_self.type+'" class="uuButton" href="#"><span><span><img src="'+universalUploader.getIcon("add")+'"/>'+universalUploader.getTranslatedString("button_browse")+'</span></span></a>'+
					'&nbsp;<a class="uuButton" href="#" id="uploadButton_'+_self.type+'" onclick="javascript: universalUploader.uploadButtonHandler();"><span><span><img src="'+universalUploader.getIcon("upload")+'"/>'+universalUploader.getTranslatedString("button_upload")+'</span></span></a>'+					
					'<form id="form_'+id+'" name="form_'+id+'" action="'+this.url+'" method="post" encoding="multipart/form-data" enctype="multipart/form-data" target="hidden_iframe" >'+
					'<input id="file_'+id+'" name="file_'+id+'" class="uuFileInput" type="file" onchange="javascript: universalUploader.Html4.addFile(this);">'+					
					'</form></div>';
				
				initialForm += '<div id="'+_self.type+'_fileList" class="fileList"></div>';
				
				return initialForm;
			};
			
			this.afterRender = function(){
				//universalUploader.setButtonState(_self.type, "browseButton_", "add", false);
				var browseButton = document.getElementById('browseButton_'+_self.type);
				var form = document.getElementById('form_'+_self.lastId);
				var input = document.getElementById('file_'+_self.lastId);
				
				universalUploader.positionFormUnderButton(browseButton, form, input);
				//universalUploader.setButtonState(_self.type, "browseButton_", "add", true);
			};
						
			/**
			 * method can be called by event handler
			 * @param fileInput
			 */
			this.addFile= function(param){
				fileInput = this;
				addedfiles = [];				
				if(param && param.type=="file")
				{
					fileInput = param;
										
				}
				else if(param && param.srcElement)
					fileInput = param.srcElement;
				var cid = fileInput.id.substring(5);
				var file = new universalUploader.File(cid, fileInput.value, -1);
				var invalidFiles = [0,0,0,0,0,0,0,0,0,0,0,0];
				var res = universalUploader.isValidFile(file, addedfiles, 0);
				if(res >=0)
				{
					invalidFiles[res]++;
					universalUploader.displayResultOfAdd(invalidFiles);
				}
				else
				{
					//universalUploader.removeEventListener('click', browseButton);
					
					fileInput.style.display="none";
					document.getElementById("form_"+cid).style.display="none";
					/* Contents div should be defined for all of uploaders */
					var _self = universalUploader.Html4; 
					var tabContents = document.getElementById(_self.type+"_content");
					/* removeEventListener from current file input  */
					universalUploader.removeEventListener('change', fileInput, _self.addFile);
					//remove attribute with change event handler
					fileInput.removeAttribute("onchange");
					/*
					 * Append new form with file input to tab contents
					 * check for count of selected files here
					 */
					if(tabContents)
					{
						var id = universalUploader.getUid();
						fForm = document.createElement('form');
						fForm.setAttribute('id', 'form_'+id);				
						fForm.setAttribute('name', 'form_'+id);
						fForm.setAttribute('enctype', 'multipart/form-data');
						fForm.setAttribute('encoding', 'multipart/form-data');
						fForm.setAttribute('method', 'post');
						fForm.setAttribute('action', _self.url);
						fForm.setAttribute('target', 'hidden_iframe');				
	
	
						fileInput = document.createElement('input');
						fileInput.setAttribute('id', 'file_'+id);						
						fileInput.setAttribute('name', _self.options.postFields_file);
						fileInput.setAttribute('type', 'file');
						fileInput.className='uuFileInput';
						//listen for change event to add new file
						universalUploader.addEventListener('change', fileInput, _self.addFile);
						fForm.appendChild(fileInput);					
						
						document.getElementById("form_"+cid).parentNode.insertBefore(fForm,document.getElementById("form_"+cid).nextSibling); 
						//tabContents.appendChild(fForm);
						_self.ids.push(cid);
						addedfiles.push(file);
						var browseButton = document.getElementById('browseButton_'+_self.type);	
						browseButton.onClick = null;
						universalUploader.positionFormUnderButton(browseButton, fForm, fileInput);
					}
					//Internal files store. It allow us to switch tabs without loosing file objects
					for (var i = 0; i < addedfiles.length; i++) {
						_self._files[addedfiles[i].id] = addedfiles[i];
						_self._files.push(addedfiles[i]);
					}
					if(addedfiles.length > 0)
						 universalUploader.onAddFiles(_self.type, addedfiles);
				}
			};
			/**
			 * Return current file list
			 */
			this.getFiles = function(){
				/*var addedfiles =[];				
				for (var i = 0; i < _self.ids.length; i++) {
		                var file = document.getElementById('file_'+_self.ids[i]);
		                var ufile = null;
		                if (file)
		                {
		                	ufile = new universalUploader.File(_self.ids[i], file.value, -1);
		                	addedfiles[_self.ids[i]] = ufile;
		                    addedfiles.push(ufile);
		                }
		        }	*/	
				return _self._files;
			};
			
			/**
			 * RemoveAll files from list
			 */
			this.clearList = function(){
				universalUploader.onClearList(_self.type);
				for (var i = _self._files.length-1; i >= 0; i--) 
					_self.removeFile(_self._files[i].id, true);
			};
						
			/**
			 * Remove file object by its id
			 */
			this.removeFile = function(fileId, doNotCallEventHandler){				
				universalUploader.onRemoveFile(_self.type, fileId, doNotCallEventHandler);
				if(_self.ids.indexOf(fileId) >=0 )
					_self.ids.splice(_self.ids.indexOf(fileId),1);
				var file = document.getElementById('file_'+fileId);
				var form = document.getElementById('form_'+fileId);
				if(file)
					file.parentNode.removeChild(file);
				if(form)
					form.parentNode.removeChild(form);
				if(_self._files[fileId])
				{
					_self._files.splice(_self._files.indexOf(_self._files[fileId]),1);
					_self._files[fileId] = null;
					delete _self._files[fileId];
				}				
			};
			
			/**
			 * Start upload process
			 */
			this.upload= function(){
				_self.stopped=false;
				 universalUploader.onUploadStart(_self.type);
				_self.uploadQueue=this.ids.slice();
				_self.uploadNext();
			};
			/**
			 * Start upload process
			 */
			this.stop= function(){
				if(!_self.stopped)
				{					
					if (/MSIE (\d+\.\d+);/.test(navigator.userAgent))
			            window.frames["hidden_iframe"].window.document.execCommand('Stop');
			        else
			            window.frames["hidden_iframe"].window.stop();
					_self.stopped = true;
					if(_self._files.length >0)
						universalUploader.onFileUploadStop(_self.type, _self.currentId);
				}
			};
			
			/**
			 * Append file to upload queue while upload process is active
			 * @param fToAppend
			 */
			this.appendToUploadQueue = function(fToAppend){
				for (var i = fToAppend.length-1; i >= 0; i--) 
					_self.uploadQueue.push(fToAppend[i].id);
			};
			
			/**
			 * Upload next file
			 */
			this.uploadNext = function(){
				if(!_self.stopped)
				{					
					_self.currentId = _self.uploadQueue.shift();
					/*
					 * If there is some file for upload 
					 */
					var input = document.getElementById('file_'+_self.currentId);
					if(_self.currentId && input && input.value && 
						_self._files[_self.currentId] && _self._files[_self.currentId].status != universalUploader.FILE_COMPLETE)
					{	
							//Fire file start event here
						_self.doFileUpload(_self.currentId);					
					}			
					else
						if(_self.uploadQueue.length >0)
							_self.uploadNext();
						else
							universalUploader.onUploadComplete(_self.type);//call complete method here
				}
			};
			
			this.doFileUpload = function(id){
	            var fid = _self.currentId,                
	            	frame = document.getElementById('hidden_iframe'),                
	            	form = document.getElementById('form_'+fid);  
	            //reset src of frame before start upload process
	            frame.src= window.ActiveXObject ? 'javascript:""' : 'about:blank';
	            
	            /*Add post fields individual for each file/request*/
	            _self.postFields[_self.options.postFields_fileId] = fid;
	            _self.postFields[_self.options.postFields_fileSize] = _self._files[fid].size;
	            _self.postFields[_self.options.postFields_filesCount] = _self._files.length;
				
	            //Append hidden form fields to each form
	            for(key in _self.postFields) 
					if (_self.postFields.hasOwnProperty(key))
					{
						postField = document.createElement('input');
						postField.setAttribute('id', key+id);
						postField.setAttribute('name', key);
						postField.setAttribute('value', _self.postFields[key]);
						postField.setAttribute('type', 'hidden');	
						form.appendChild(postField);
					}
	           	           
	            if(window.ActiveXObject)
	               document.frames[frame.id].name = frame.id;

	            // add dynamic params
	            /*Ext.iterate(Ext.urlDecode(ps, false), function(k, v){
	                hd = doc.createElement('input');
	                Ext.fly(hd).set({
	                    type: 'hidden',
	                    value: v,
	                    name: k
	                });
	                form.appendChild(hd);
	                hiddens.push(hd);
	            });*/

	            function cb(){
	                var me = this,
	                    // bogus response object
	                    resp = '',
	                    doc,
	                    firstChild;

	                try{
	                    doc = frame.contentWindow.document || frame.contentDocument || WINDOW.frames[id].document;
	                    if(doc){
	                        if(doc.body){
	                            if(/textarea/i.test((firstChild = doc.body.firstChild || {}).tagName)){ 
	                            	resp = firstChild.value;
	                            }else{
	                            	resp = doc.body.innerHTML;
	                            }
	                        }
	                        else
	                        	resp = doc.documentElement.innerText || doc.documentElement.textContent;                      
	                    }	                   
		                //fire complete event here		                
		                _self.fileComplete(fid, resp);
	                }
	                catch(e) 
	                {
	                	//fire error event here	                	
	                	_self.fileError(id, e);
	                } 
	                universalUploader.removeEventListener('load', frame, cb);
	            }
	            
	            if(universalUploader.onFileUploadStart(_self.type, _self.currentId) === false)
	            	return;	            
	            frame.onLoad=null;
	            universalUploader.addEventListener('load', frame, cb);	            
	            form.submit();            
	        };
	        
	        this.fileComplete= function(fid, response){
	        	//fire complete event	    					
                universalUploader.onFileUploadComplete(_self.type, _self.currentId, response);
	        	//going to the next file
	        	_self.uploadNext();
	        };
	        this.fileError = function(fid, e){
	        	//mark file as "error"
	        	 universalUploader.onFileUploadError(_self.type, _self.currentId, "",  e.message);
	        };
		}
};

universalUploader.Html5 = {
		type:'drag-and-drop',
		available: true,
		formid: "",
		init: function(options)
		{
			var _self = this;
			_self.currentState = universalUploader.FILE_READY;
			this.options = options;
			this.progressInfo = new universalUploader.ProgressInfo();
			this.id = universalUploader.getUid();
			this.url = options.url;
			this.postFields = options.postFields;
			this.timeout = options.timeout ? options.timeout : 3000;
			//array of selected files
			this.files = [];
			//internal files store 
			this._files = [];
			this.ids= [];
			this.currentId = null;
			this.dndTimeout = null;
			
			if (window.XMLHttpRequest) 
			{
				xhr = new XMLHttpRequest();				
				_self.available = (xhr.sendAsBinary || xhr.upload) ? true: false;
				try {
					var fd = new FormData();
				}
				catch(err)
				{
					_self.available = false;
				}		       
			}
			else
				_self.available = false;
			if(!_self.available)
				return false;
	        /**
			 * Render upload form
			 * @returns {String}
			 */
			this.render= function (uploader){
				//_self.id = universalUploader.getUid();
				
				
				var initialForm = '<div id="controlsContainer_'+_self.type+'" class="uuControlsContainer">'+
					'<a class="uuButton uuClearButton" href="#" id="clearButton_'+_self.type+'" onclick="javascript: universalUploader.clearList();"><span><span><img src="'+universalUploader.getIcon("clear")+'"/>'+universalUploader.getTranslatedString("button_clear")+'</span></span></a>'+
					'<a id="browseButton_'+_self.type+'" class="uuButton" href="#"><span><span><img src="'+universalUploader.getIcon("add")+'"/>'+universalUploader.getTranslatedString("button_browse")+'</span></span></a>'+					
					'&nbsp;<a class="uuButton" href="#" id="uploadButton_'+_self.type+'" onclick="javascript: universalUploader.uploadButtonHandler();"><span><span><img src="'+universalUploader.getIcon("upload")+'"/>'+universalUploader.getTranslatedString("button_upload")+'</span></span></a>'+					
					'<form id="form_'+_self.id+'" name="form_'+_self.id+'" action="'+_self.url+'" method="post" encoding="multipart/form-data" enctype="multipart/form-data" target="hidden_iframe" >'+
					'<input id="file_'+_self.id+'" name="file_'+_self.id+'" type="file" class="uuFileInput" onchange="javascript: universalUploader.Html5.addFiles(this);" multiple="multiple">'+
					'</form></div>';		
				initialForm += '<div id="drop_target" class="dropTarget"> Drop files here </div>';
				initialForm += '<div id="'+_self.type+'_fileList" class="fileList"></div>';
				return initialForm;
			};
			
			this.afterRender = function(){
				//universalUploader.setButtonState(_self.type, "browseButton_", "add", false);
				var dropTarget = document.getElementById("drop_target");
				universalUploader.addEventListener("dragenter", document.body, function(e){
					    var list = document.getElementById(_self.type+'_fileList');
						dropTarget = document.getElementById("drop_target");
						dropTarget.style.display="block";
						dropTarget.style.width=list.clientWidth+'px';
						dropTarget.style.height=list.clientHeight+'px';
						if(_self.dndTimeout)
							clearTimeout(_self.dndTimeout);
						_self.dndTimeout = setTimeout( _self.dragend, 3000 );
						if(navigator.userAgent.indexOf('Safari') > 0)
						{
							var form = document.getElementById('form_'+_self.id);
							var input = document.getElementById('file_'+_self.id);
							try{
								form.removeChild(input);
								universalUploader.addEventListener("change", input, _self.dragend);
							}catch(err){}
							dropTarget.appendChild(input);
							universalUploader.positionFormUnderButton(dropTarget, form, input);
							/*input.style.position = 'absolute';
							input.style.display = 'block';
							input.style.top = 0;
							input.style.left = 0;						
							input.style.opacity = 0;*/
						}
					});
				universalUploader.addEventListener("dragstart", document.body, _self.dragstart);
				universalUploader.addEventListener("dragend", document.body, _self.dragend);
				if(dropTarget && navigator.userAgent.indexOf('Safari') < 0)
				{
					universalUploader.addEventListener("dragover", dropTarget, _self.dragOver);
					universalUploader.addEventListener("drop", dropTarget, _self.drop);
				}
				_self.placeInputOverBrowseButton();
				//universalUploader.setButtonState(_self.type, "browseButton_", "add", true);
			};
			
			this.dragstart = function (e) {
			    try
			    {
			        if (e.preventDefault) e.preventDefault();
			        if (e.stopPropagation) e.stopPropagation();
			        if (window.event) window.event.returnValue = false;
			        if (e.stopEvent) e.stopEvent();
			    }catch(e){ if(debugmode) throw e;};
			    return false;
			};
			
			this.dragOver = function (e) {
			    try
			    {
			    	if(_self.dndTimeout)
						clearTimeout(_self.dndTimeout);
					_self.dndTimeout = setTimeout( _self.dragend, 3000 );
			        if (e.preventDefault) e.preventDefault();
			        if (e.stopPropagation) e.stopPropagation();
			        if (window.event) window.event.returnValue = false;
			        if (e.stopEvent) e.stopEvent();
			    }catch(e){ if(debugmode) throw e;};
			    return false;
			};
			
			this.dragend = function (e) {
				try
			    {
					_self.placeInputOverBrowseButton();
					if(_self.dndTimeout)
						clearTimeout(_self.dndTimeout);
			    	var dropTarget = document.getElementById("drop_target");
					dropTarget.style.display="none";
					var input = document.getElementById('file_'+_self.id);
					universalUploader.removeEventListener("change", input, _self.dragend);
			    } 
			    catch(e){ throw e;};
			};
			
			this.drop = function (e) {
			    try
			    {
			    	_self.placeInputOverBrowseButton();
			    	var dropTarget = document.getElementById("drop_target");
					dropTarget.style.display="none";
					
			        if (e.preventDefault) e.preventDefault();
			        if (e.stopPropagation) e.stopPropagation();
			        if (window.event) window.event.returnValue = false;
			        if (e.stopEvent) e.stopEvent();
			        
			        if (!e.dataTransfer || !e.dataTransfer.files || e.dataTransfer.files.length < 1)
			            return;
			        
			        _self._addFiles(e.dataTransfer.files);			            
			    } 
			    catch(e){ throw e;};
			};

			this.placeInputOverBrowseButton = function(){
				
				var dropTarget = document.getElementById("drop_target");
				var container = document.getElementById('controlsContainer_'+_self.type);
				var browseButton = document.getElementById('browseButton_'+_self.type);
				var form = document.getElementById('form_'+_self.id);
				var input = document.getElementById('file_'+_self.id);
				try{
					dropTarget.removeChild(input);
				}catch(err){}
				form.appendChild(input);
				universalUploader.positionFormUnderButton(browseButton, form, input);
			};
			
			/**
			 * Method called when user select files with browse button
			 */
			this.addFiles = function(item){				
				this._addFiles(item.files);
			};
			/**
			 * Internal method
			 * @param files - array of selected or dropped files
			 */
			this._addFiles = function(afiles){
				var addedfiles =[], file = null, tSize=0, invalidFiles = [0,0,0,0,0,0,0,0,0,0,0,0], res =-1, invalidFilesCount =0;
				/* generate uid for each file item
				store native file objects in associative array */
				//Create new File object for each item
				 for (var i = 0; i < afiles.length; i++) {
		                var file = afiles[i];
		                if (file.name != "") {
		                	var fid =  universalUploader.getUid();
		                    _self.files[fid] = file;
		                    _self.files.push(file);		                    
		                    _self.ids.push(fid);
		                    file = new universalUploader.File(fid, file.name || file.fileName, file.size || file.fileSize);
		                    res = universalUploader.isValidFile(file, addedfiles, tSize);
		                    if(res < 0)
		                    {
		                    	addedfiles.push(file);
		                    	tSize += file.size;
		                    }
		                    else
		                    {
		                    	invalidFiles[res]++;
		                    	invalidFilesCount ++;
		                    }
		                }
		            }
				 
				
				if(invalidFilesCount > 0)
					universalUploader.displayResultOfAdd(invalidFiles);

				//Internal files store. It allow us to switch tabs without loosing file objects
				for (var i = 0; i < addedfiles.length; i++) {
					_self._files[addedfiles[i].id] = addedfiles[i];
					_self._files.push(addedfiles[i]);
				}
				 if(addedfiles.length > 0)
					 universalUploader.onAddFiles(_self.type, addedfiles);
			};	
			
			/**
			 * Return array of File objects (newly created)
			 * @returns {Array}
			 */
			this.getFiles = function(){
				/*var addedfiles =[];				
				for (var i = 0; i < _self.ids.length; i++) {
		                var file = _self.files[_self.ids[i]];
		                var ufile = null;
		                if (file)
		                {
		                	ufile = new universalUploader.File(_self.ids[i], file.name || file.fileName, file.size || file.fileSize);
		                	addedfiles[_self.ids[i]] = ufile;
		                    addedfiles.push(ufile);
		                }
		        }	*/	
				return _self._files;
			};
			
			/**
			 * RemoveAll files from list
			 */
			this.clearList = function(){
				universalUploader.onClearList(_self.type);
				for (var i = _self._files.length-1; i >= 0; i--) 
					_self.removeFile(_self._files[i].id, true);
			};
			
			this.removeFile = function(fileId, doNotCallEventHandler){
				universalUploader.onRemoveFile(_self.type, fileId, doNotCallEventHandler);
				if(_self.ids.indexOf(fileId) >=0)
					_self.ids.splice(_self.ids.indexOf(fileId),1);
				if(_self.files[fileId])
				{
					_self.files.splice(_self.files.indexOf(_self.files[fileId]),1);
					_self.files[fileId] = null;
					delete _self.files[fileId];
				}				
				if(_self._files[fileId])
				{
					_self._files.splice(_self._files.indexOf(_self._files[fileId]),1);
					_self._files[fileId] = null;
					delete _self._files[fileId];
				}
			};
			
			/**
			 * Stop upload process
			 */
			this.stop= function(){
				if(!_self.stopped)
				{
					_self.stopped = true;
					if (_self.xhr && _self.xhr.readyState != 4)
						 _self.xhr.abort();
				}
			};
			
			/**
			 * Start upload process
			 */
			this.upload= function(){
				universalUploader.onUploadStart(_self.type);
				_self.stopped = false;
				_self.uploadQueue=_self.ids.slice();
				_self.uploadNext();
			};
			
			/**
			 * Append file to upload queue while upload process is active
			 * @param fToAppend
			 */
			this.appendToUploadQueue = function(fToAppend){
				for (var i = fToAppend.length-1; i >= 0; i--) 
					_self.uploadQueue.push(fToAppend[i].id);
			};
			/**
			 * Upload next file
			 */
			this.uploadNext = function(){
				_self.currentId = _self.uploadQueue.shift();
				/*
				 * If there is some file for upload 
				 */				
				if(_self.currentId && !_self.stopped && 
						_self._files[_self.currentId] && _self._files[_self.currentId].status != universalUploader.FILE_COMPLETE)
				{	
						//Fire file start event here
					_self.doFileUpload(_self.currentId);					
				}			
				else
					if(_self.uploadQueue.length > 0)
						_self.uploadNext();
					else
						//fire upload complete event
						universalUploader.onUploadComplete(_self.type);
			};
			/**
			 * Do single file upload
			 * @param file
			 */
			this.doFileUpload = function (id) {
					var file = _self.files[id];
					xhr = new XMLHttpRequest();
					_self.xhr=xhr;
			        xhr.upload.addEventListener("loadstart", function (e) {
			        	universalUploader.onFileUploadStart(_self.type, _self.currentId);			            		            
			        }, false);

			        xhr.upload.addEventListener("progress", function (e) {			        	
			            if (e.lengthComputable && e.total > 0) 
			            {
			            	var file = _self._files[_self.currentId];
			            	universalUploader.onFileUploadProgress(_self.type, _self.currentId, e.loaded > file.size ? file.size : e.loaded);
			            }
			        }, false);

			        xhr.upload.addEventListener("error", function (e) {
			        	if(_self.currentId)
			        		universalUploader.onFileUploadError(_self.type, _self.currentId, xhr.status.toString(), xhr.statusText);			        	
			            _self.uploadNext();
			        }, false);

			        xhr.upload.addEventListener("abort", function (e) {
			        	universalUploader.onFileUploadStop(_self.type, _self.currentId);		            
			        }, false);
			        
			        xhr.onreadystatechange = function () {
			            if (xhr.readyState == 4 && !_self.stopped) {
			                var res = xhr.responseText;
			                /*try { res = eval("(" + xhr.responseText + ')'); } catch (e) { res = xhr.responseText; }
			                if (res.success) {
			                	file.status = universalUploader.FILE_COMPLETE;
					            file.bytesLoaded = file.size;
			                    file.error = "";
			                } else {
			                	file.status = universalUploader.FILE_ERROR;
					            file.bytesLoaded = 0;
					            file.error = xhr.status.toString() + ". " + xhr.statusText + ".";
			                }*/
			                if(xhr.status >=200 && xhr.status < 300)
			                	universalUploader.onFileUploadComplete(_self.type, _self.currentId, res);
			                else
			                	universalUploader.onFileUploadError(_self.type, _self.currentId, xhr.status.toString(), res);
			                _self.uploadNext();
			            }
			        };

			        xhr.timeout = _self.timeout;
			        xhr.open("POST", _self.url, true);
			       
			       try{
			    	   var fd = new FormData();
			       
				        //set file field name here			        
						fd.append(_self.options.postFields_file, file);
						/*Add post fields individual for each file/request*/
			            _self.postFields[_self.options.postFields_fileId] = file.id;
			            _self.postFields[_self.options.postFields_fileSize] = file.size;
			            _self.postFields[_self.options.postFields_filesCount] = _self._files.length;
						for(key in _self.postFields) 
							if (_self.postFields.hasOwnProperty(key))
								fd.append(key, _self.postFields[key]);							
			       }
			       catch(error){
			    	   //universalUploader.onFileUploadError(_self.type, _self.currentId, 0, error);
			       }
					//append configured form fields here
					
			       xhr.send(fd); 

			        
			    };			
		}		
};

String.prototype.trim=function trim12 () {
	var str1 = this.replace(/^\s\s*/, ""),
	ws = /\s/,
	i = str1.length;
	while (ws.test(str1.charAt(-i)));
	return str1.slice(0, i + 1);
};

/*[].indexOf || (Array.prototype.indexOf = function(v,n){
	  n = (n==null)?0:n; var m = this.length;
	  for(var i = n; i < m; i++)
	    if(this[i] == v)
	       return i;
	  return -1;
	});*/

if(Array.indexOf = 'undefined' || !Array.indexOf)
{
	Array.prototype.indexOf = function(obj)
	{
		for(var i=0; i<this.length; i++)
		{
			if(this[i]==obj)
			{
				return i;
			}
		}
		return -1;
	};
}
/*	
 * SWFObject v2.2 <http://code.google.com/p/swfobject/> 
is released under the MIT License <http://www.opensource.org/licenses/mit-license.php> 
*/
swfobject = function(){var D="undefined",r="object",S="Shockwave Flash",W="ShockwaveFlash.ShockwaveFlash",q="application/x-shockwave-flash",R="SWFObjectExprInst",x="onreadystatechange",O=window,j=document,t=navigator,T=false,U=[h],o=[],N=[],I=[],l,Q,E,B,J=false,a=false,n,G,m=true,M=function(){var aa=typeof j.getElementById!=D&&typeof j.getElementsByTagName!=D&&typeof j.createElement!=D,ah=t.userAgent.toLowerCase(),Y=t.platform.toLowerCase(),ae=Y?/win/.test(Y):/win/.test(ah),ac=Y?/mac/.test(Y):/mac/.test(ah),af=/webkit/.test(ah)?parseFloat(ah.replace(/^.*webkit\/(\d+(\.\d+)?).*$/,"$1")):false,X=!+"\v1",ag=[0,0,0],ab=null;if(typeof t.plugins!=D&&typeof t.plugins[S]==r){ab=t.plugins[S].description;if(ab&&!(typeof t.mimeTypes!=D&&t.mimeTypes[q]&&!t.mimeTypes[q].enabledPlugin)){T=true;X=false;ab=ab.replace(/^.*\s+(\S+\s+\S+$)/,"$1");ag[0]=parseInt(ab.replace(/^(.*)\..*$/,"$1"),10);ag[1]=parseInt(ab.replace(/^.*\.(.*)\s.*$/,"$1"),10);ag[2]=/[a-zA-Z]/.test(ab)?parseInt(ab.replace(/^.*[a-zA-Z]+(.*)$/,"$1"),10):0}}else{if(typeof O.ActiveXObject!=D){try{var ad=new ActiveXObject(W);if(ad){ab=ad.GetVariable("$version");if(ab){X=true;ab=ab.split(" ")[1].split(",");ag=[parseInt(ab[0],10),parseInt(ab[1],10),parseInt(ab[2],10)]}}}catch(Z){}}}return{w3:aa,pv:ag,wk:af,ie:X,win:ae,mac:ac}}(),k=function(){if(!M.w3){return}if((typeof j.readyState!=D&&j.readyState=="complete")||(typeof j.readyState==D&&(j.getElementsByTagName("body")[0]||j.body))){f()}if(!J){if(typeof j.addEventListener!=D){j.addEventListener("DOMContentLoaded",f,false)}if(M.ie&&M.win){j.attachEvent(x,function(){if(j.readyState=="complete"){j.detachEvent(x,arguments.callee);f()}});if(O==top){(function(){if(J){return}try{j.documentElement.doScroll("left")}catch(X){setTimeout(arguments.callee,0);return}f()})()}}if(M.wk){(function(){if(J){return}if(!/loaded|complete/.test(j.readyState)){setTimeout(arguments.callee,0);return}f()})()}s(f)}}();function f(){if(J){return}try{var Z=j.getElementsByTagName("body")[0].appendChild(C("span"));Z.parentNode.removeChild(Z)}catch(aa){return}J=true;var X=U.length;for(var Y=0;Y<X;Y++){U[Y]()}}function K(X){if(J){X()}else{U[U.length]=X}}function s(Y){if(typeof O.addEventListener!=D){O.addEventListener("load",Y,false)}else{if(typeof j.addEventListener!=D){j.addEventListener("load",Y,false)}else{if(typeof O.attachEvent!=D){i(O,"onload",Y)}else{if(typeof O.onload=="function"){var X=O.onload;O.onload=function(){X();Y()}}else{O.onload=Y}}}}}function h(){if(T){V()}else{H()}}function V(){var X=j.getElementsByTagName("body")[0];var aa=C(r);aa.setAttribute("type",q);var Z=X.appendChild(aa);if(Z){var Y=0;(function(){if(typeof Z.GetVariable!=D){var ab=Z.GetVariable("$version");if(ab){ab=ab.split(" ")[1].split(",");M.pv=[parseInt(ab[0],10),parseInt(ab[1],10),parseInt(ab[2],10)]}}else{if(Y<10){Y++;setTimeout(arguments.callee,10);return}}X.removeChild(aa);Z=null;H()})()}else{H()}}function H(){var ag=o.length;if(ag>0){for(var af=0;af<ag;af++){var Y=o[af].id;var ab=o[af].callbackFn;var aa={success:false,id:Y};if(M.pv[0]>0){var ae=c(Y);if(ae){if(F(o[af].swfVersion)&&!(M.wk&&M.wk<312)){w(Y,true);if(ab){aa.success=true;aa.ref=z(Y);ab(aa)}}else{if(o[af].expressInstall&&A()){var ai={};ai.data=o[af].expressInstall;ai.width=ae.getAttribute("width")||"0";ai.height=ae.getAttribute("height")||"0";if(ae.getAttribute("class")){ai.styleclass=ae.getAttribute("class")}if(ae.getAttribute("align")){ai.align=ae.getAttribute("align")}var ah={};var X=ae.getElementsByTagName("param");var ac=X.length;for(var ad=0;ad<ac;ad++){if(X[ad].getAttribute("name").toLowerCase()!="movie"){ah[X[ad].getAttribute("name")]=X[ad].getAttribute("value")}}P(ai,ah,Y,ab)}else{p(ae);if(ab){ab(aa)}}}}}else{w(Y,true);if(ab){var Z=z(Y);if(Z&&typeof Z.SetVariable!=D){aa.success=true;aa.ref=Z}ab(aa)}}}}}function z(aa){var X=null;var Y=c(aa);if(Y&&Y.nodeName=="OBJECT"){if(typeof Y.SetVariable!=D){X=Y}else{var Z=Y.getElementsByTagName(r)[0];if(Z){X=Z}}}return X}function A(){return !a&&F("6.0.65")&&(M.win||M.mac)&&!(M.wk&&M.wk<312)}function P(aa,ab,X,Z){a=true;E=Z||null;B={success:false,id:X};var ae=c(X);if(ae){if(ae.nodeName=="OBJECT"){l=g(ae);Q=null}else{l=ae;Q=X}aa.id=R;if(typeof aa.width==D||(!/%$/.test(aa.width)&&parseInt(aa.width,10)<310)){aa.width="310"}if(typeof aa.height==D||(!/%$/.test(aa.height)&&parseInt(aa.height,10)<137)){aa.height="137"}j.title=j.title.slice(0,47)+" - Flash Player Installation";var ad=M.ie&&M.win?"ActiveX":"PlugIn",ac="MMredirectURL="+O.location.toString().replace(/&/g,"%26")+"&MMplayerType="+ad+"&MMdoctitle="+j.title;if(typeof ab.flashvars!=D){ab.flashvars+="&"+ac}else{ab.flashvars=ac}if(M.ie&&M.win&&ae.readyState!=4){var Y=C("div");X+="SWFObjectNew";Y.setAttribute("id",X);ae.parentNode.insertBefore(Y,ae);ae.style.display="none";(function(){if(ae.readyState==4){ae.parentNode.removeChild(ae)}else{setTimeout(arguments.callee,10)}})()}u(aa,ab,X)}}function p(Y){if(M.ie&&M.win&&Y.readyState!=4){var X=C("div");Y.parentNode.insertBefore(X,Y);X.parentNode.replaceChild(g(Y),X);Y.style.display="none";(function(){if(Y.readyState==4){Y.parentNode.removeChild(Y)}else{setTimeout(arguments.callee,10)}})()}else{Y.parentNode.replaceChild(g(Y),Y)}}function g(ab){var aa=C("div");if(M.win&&M.ie){aa.innerHTML=ab.innerHTML}else{var Y=ab.getElementsByTagName(r)[0];if(Y){var ad=Y.childNodes;if(ad){var X=ad.length;for(var Z=0;Z<X;Z++){if(!(ad[Z].nodeType==1&&ad[Z].nodeName=="PARAM")&&!(ad[Z].nodeType==8)){aa.appendChild(ad[Z].cloneNode(true))}}}}}return aa}function u(ai,ag,Y){var X,aa=c(Y);if(M.wk&&M.wk<312){return X}if(aa){if(typeof ai.id==D){ai.id=Y}if(M.ie&&M.win){var ah="";for(var ae in ai){if(ai[ae]!=Object.prototype[ae]){if(ae.toLowerCase()=="data"){ag.movie=ai[ae]}else{if(ae.toLowerCase()=="styleclass"){ah+=' class="'+ai[ae]+'"'}else{if(ae.toLowerCase()!="classid"){ah+=" "+ae+'="'+ai[ae]+'"'}}}}}var af="";for(var ad in ag){if(ag[ad]!=Object.prototype[ad]){af+='<param name="'+ad+'" value="'+ag[ad]+'" />'}}aa.outerHTML='<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"'+ah+">"+af+"</object>";N[N.length]=ai.id;X=c(ai.id)}else{var Z=C(r);Z.setAttribute("type",q);for(var ac in ai){if(ai[ac]!=Object.prototype[ac]){if(ac.toLowerCase()=="styleclass"){Z.setAttribute("class",ai[ac])}else{if(ac.toLowerCase()!="classid"){Z.setAttribute(ac,ai[ac])}}}}for(var ab in ag){if(ag[ab]!=Object.prototype[ab]&&ab.toLowerCase()!="movie"){e(Z,ab,ag[ab])}}aa.parentNode.replaceChild(Z,aa);X=Z}}return X}function e(Z,X,Y){var aa=C("param");aa.setAttribute("name",X);aa.setAttribute("value",Y);Z.appendChild(aa)}function y(Y){var X=c(Y);if(X&&X.nodeName=="OBJECT"){if(M.ie&&M.win){X.style.display="none";(function(){if(X.readyState==4){b(Y)}else{setTimeout(arguments.callee,10)}})()}else{X.parentNode.removeChild(X)}}}function b(Z){var Y=c(Z);if(Y){for(var X in Y){if(typeof Y[X]=="function"){Y[X]=null}}Y.parentNode.removeChild(Y)}}function c(Z){var X=null;try{X=j.getElementById(Z)}catch(Y){}return X}function C(X){return j.createElement(X)}function i(Z,X,Y){Z.attachEvent(X,Y);I[I.length]=[Z,X,Y]}function F(Z){var Y=M.pv,X=Z.split(".");X[0]=parseInt(X[0],10);X[1]=parseInt(X[1],10)||0;X[2]=parseInt(X[2],10)||0;return(Y[0]>X[0]||(Y[0]==X[0]&&Y[1]>X[1])||(Y[0]==X[0]&&Y[1]==X[1]&&Y[2]>=X[2]))?true:false}function v(ac,Y,ad,ab){if(M.ie&&M.mac){return}var aa=j.getElementsByTagName("head")[0];if(!aa){return}var X=(ad&&typeof ad=="string")?ad:"screen";if(ab){n=null;G=null}if(!n||G!=X){var Z=C("style");Z.setAttribute("type","text/css");Z.setAttribute("media",X);n=aa.appendChild(Z);if(M.ie&&M.win&&typeof j.styleSheets!=D&&j.styleSheets.length>0){n=j.styleSheets[j.styleSheets.length-1]}G=X}if(M.ie&&M.win){if(n&&typeof n.addRule==r){n.addRule(ac,Y)}}else{if(n&&typeof j.createTextNode!=D){n.appendChild(j.createTextNode(ac+" {"+Y+"}"))}}}function w(Z,X){if(!m){return}var Y=X?"visible":"hidden";if(J&&c(Z)){c(Z).style.visibility=Y}else{v("#"+Z,"visibility:"+Y)}}function L(Y){var Z=/[\\\"<>\.;]/;var X=Z.exec(Y)!=null;return X&&typeof encodeURIComponent!=D?encodeURIComponent(Y):Y}var d=function(){if(M.ie&&M.win){window.attachEvent("onunload",function(){var ac=I.length;for(var ab=0;ab<ac;ab++){I[ab][0].detachEvent(I[ab][1],I[ab][2])}var Z=N.length;for(var aa=0;aa<Z;aa++){y(N[aa])}for(var Y in M){M[Y]=null}M=null;for(var X in swfobject){swfobject[X]=null}swfobject=null})}}();return{registerObject:function(ab,X,aa,Z){if(M.w3&&ab&&X){var Y={};Y.id=ab;Y.swfVersion=X;Y.expressInstall=aa;Y.callbackFn=Z;o[o.length]=Y;w(ab,false)}else{if(Z){Z({success:false,id:ab})}}},getObjectById:function(X){if(M.w3){return z(X)}},embedSWF:function(ab,ah,ae,ag,Y,aa,Z,ad,af,ac){var X={success:false,id:ah};if(M.w3&&!(M.wk&&M.wk<312)&&ab&&ah&&ae&&ag&&Y){w(ah,false);K(function(){ae+="";ag+="";var aj={};if(af&&typeof af===r){for(var al in af){aj[al]=af[al]}}aj.data=ab;aj.width=ae;aj.height=ag;var am={};if(ad&&typeof ad===r){for(var ak in ad){am[ak]=ad[ak]}}if(Z&&typeof Z===r){for(var ai in Z){if(typeof am.flashvars!=D){am.flashvars+="&"+ai+"="+Z[ai]}else{am.flashvars=ai+"="+Z[ai]}}}if(F(Y)){var an=u(aj,am,ah);if(aj.id==ah){w(ah,true)}X.success=true;X.ref=an}else{if(aa&&A()){aj.data=aa;P(aj,am,ah,ac);return}else{w(ah,true)}}if(ac){ac(X)}})}else{if(ac){ac(X)}}},switchOffAutoHideShow:function(){m=false},ua:M,getFlashPlayerVersion:function(){return{major:M.pv[0],minor:M.pv[1],release:M.pv[2]}},hasFlashPlayerVersion:F,createSWF:function(Z,Y,X){if(M.w3){return u(Z,Y,X)}else{return undefined}},showExpressInstall:function(Z,aa,X,Y){if(M.w3&&A()){P(Z,aa,X,Y)}},removeSWF:function(X){if(M.w3){y(X)}},createCSS:function(aa,Z,Y,X){if(M.w3){v(aa,Z,Y,X)}},addDomLoadEvent:K,addLoadEvent:s,getQueryParamValue:function(aa){var Z=j.location.search||j.location.hash;if(Z){if(/\?/.test(Z)){Z=Z.split("?")[1]}if(aa==null){return L(Z)}var Y=Z.split("&");for(var X=0;X<Y.length;X++){if(Y[X].substring(0,Y[X].indexOf("="))==aa){return L(Y[X].substring((Y[X].indexOf("=")+1)))}}}return""},expressInstallCallback:function(){if(a){var X=c(R);if(X&&l){X.parentNode.replaceChild(l,X);if(Q){w(Q,true);if(M.ie&&M.win){l.style.display="block"}}if(E){E(B)}}a=false}}}}();

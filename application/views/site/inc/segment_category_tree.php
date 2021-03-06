<!-- the tree container-->
<div id="seg_cats_ph" class="demo"></div>
<input type="hidden" name="hdn_seg_cats" id="hdn_seg_cats" value="<?=@$hdn_seg_cats?>" />
<!-- Definition of context menu -->
<ul id="myMenu" class="contextMenu">
    <li class="create"><a href="#create">Create</a></li>
    <li class="edit"><a href="#edit">Edit</a></li>
    <!-- <li class="cut separator"><a href="#cut">Cut</a></li> -->
    <li class="copy"><a href="#copy">Copy</a></li>
    <li class="paste"><a href="#paste">Paste</a></li>
    <li class="delete"><a href="#delete">Delete</a></li>
</ul>


<!-- JavaScript neccessary for the tree -->
<script type="text/javascript" class="source below">
    
    $j(function () {        
      
      var tree_elmt = $j("#seg_cats_ph");
      var _activeKey = null;        
      // --- Implement Cut/Copy/Paste --------------------------------------------
      var clipboardNode = null;
      var pasteMode = null;
    
      function copyPaste(action, node) {
        switch( action ) {
            case "cut":
            case "copy":
                if(node.parent.parent==null){
                    jAlert('You cannot copy or cut the Root Category', 'Alert')
                    return false;
                 }
                clipboardNode = node;
                pasteMode = action;
                break;
            case "paste":
              if( !clipboardNode ) {
                alert("Clipoard is empty.");
                break;
              }
              if( pasteMode == "cut" ) {
                // Cut mode: check for recursion and remove source
                var isRecursive = false;
                var cb = clipboardNode.toDict(true, function(dict){
                  // If one of the source nodes is the target, we must not move
                  if( dict.key == node.data.key )
                    isRecursive = true;
                });
                if( isRecursive ) {
                  alert("Cannot move a node to a sub node.");
                  return;
                }
                node.addChild(cb);
                clipboardNode.remove();
              } else {
                  var tdict_title;
                // Copy mode: prevent duplicate keys:
                var cb = clipboardNode.toDict(true, function(dict){                    
                  dict.title = "Copy of " + dict.title;
                  tdict_title = dict.title;
                  delete dict.key; // Remove key, so a new one will be created
                });
                node.addChild(cb);
                $j.post(_site_url+'ajax/dynatree_create_node', { title: tdict_title, parent: node.data.key, lbt: 'segment_category' }, function(data) {
                                     tree_elmt.dynatree("enable");
                                     tree_elmt.dynatree("getTree").reload();
                                });
                
                
              }
              clipboardNode = pasteMode = null;
              break;
            
        default:
          alert("Unhandled clipboard action '" + action + "'");
        }
      };
    
      function createNode(parent_node){
          
         if( !parent_node )
            var parent_node = tree_elmt.dynatree("getActiveNode");
         
         var child_node = parent_node.addChild({
            title: "New Category",
            tooltip: "  ",
            isFolder: true,
            href: 'javascript:void(0)'
          });  
          
          parent_node.expand(true);
          
          editNode(child_node, 'add')
      }
      function removeNode(node){                                 
         if(node.parent.parent==null){
            jAlert('You cannot delete the Root Category', 'Alert')
            return false;
         }else{             
             $j.post(_site_url+'ajax/dynatree_remove_node', { key: node.data.key, lbt: 'segment_category' }, function(data) {                  
                  //tree_elmt.dynatree("getTree").reload();                  
                  node.remove()
             });
         }         
         
      }
      function editNode(node, mode){
          
          if($j.type(mode) == 'undefined') mode = 'edit';
          
          if(node.parent.parent==null){
            jAlert('You cannot edit the Root Category', 'Alert')
            return false;
          }
          
          var prevTitle = node.data.title, tree = node.tree;
          // Disable dynatree mouse- and key handling
          tree.$widget.unbind();
          // Replace node with <input>
          $j(".dynatree-title", node.span).html("<input id='editNode' value='" + prevTitle + "'>");
          // Focus <input> and bind keyboard handler
          $j("input#editNode")
            .focus()
            .keydown(function(event){
              switch( event.which ) {
              case 27: // [esc]
                // discard changes on [esc]
                $j("input#editNode").val(prevTitle);
                $j(this).blur();
                break;
              case 13: // [enter]
                // simulate blur to accept new value
                $j(this).blur();
                break;
              }
            }).blur(function(event){
              // Accept new value, when user leaves <input>
              var title = $j("input#editNode").val();
              node.setTitle(title);
              // Re-enable mouse and keyboard handlling
              tree.$widget.bind();
              node.focus();              
              tree_elmt.dynatree("disable");
              
              if(mode == 'add'){
                  $j.post(_site_url+'ajax/dynatree_create_node', { title: title, parent: node.parent.data.key, lbt: 'segment_category' }, function(data) {
                      //tree_elmt.dynatree("getTree").getNodeByKey(_activeKey).reloadChildren();
                      //node.parent.reloadChildren();
                     // $j.ui.dynatree.getNode
                     tree_elmt.dynatree("enable");
                     tree_elmt.dynatree("getTree").reload();                  
                     //tree_elmt.dynatree("getTree").activateKey(data.key);
                     //tree_elmt.dynatree("getTree").getNodeByKey(data.key).activate();
                     // console.log(node)
                     //var node = tree_elmt.dynatree("getActiveNode");
                     //node.data.key = data.key;
                     //node.li.id = data.key
                     //node.render();
                  });
              }else{
                  $j.post(_site_url+'ajax/dynatree_edit_node', { title: title, key: node.data.key, lbt: 'segment_category' }, function(data) {                     
                     tree_elmt.dynatree("enable");                     
                  });
              }
              
            });
      }

        
      // --- Contextmenu helper --------------------------------------------------
      function bindContextMenu(span) {
        // Add context menu to this node:
        $j(span).contextMenu({menu: "myMenu"}, function(action, el, pos) {
          // The event was bound to the <span> tag, but the node object is stored in the parent <li> tag
          var node = $j.ui.dynatree.getNode(el);
          
          switch( action ) {
              case "cut":    
              case "copy":
              case "paste":     copyPaste(action, node); break;
              case "create":    createNode(node);        break;
              case "edit":      editNode(node);          break;
              case "delete":    removeNode(node);        break;      
              default:          alert("Todo: appply action '" + action + "' to node " + node);
           }
        });
      };

        
     
     
     tree_elmt.dynatree({
        checkbox: true,             
        //classNames: {checkbox: "dynatree-radio"},
        selectMode: 1,
        fx: { height: "toggle", duration: 100 },
        autoCollapse: false,
        onSelect: function(select, node) {
            var s = node.tree.getSelectedNodes();
            
            var selKeys = $j.map(s, function(node1){
                return node1.data.key;
            }).join(', ');
            
            $j("#hdn_seg_cats").val(selKeys);
        },
        onActivate: function(node) {
            _activeKey = node.data.key;
        },
        initAjax: {
            url: _site_url+'ajax/dynatree_data',
            dataType: "json",
            timeout: 10000, // timeout, otherwise 'connection refused' is not recognized if server is not running
            data: {
                key: "",     
                checked: $j('#hdn_seg_cats').val(),
                mode: "baseFolders", 
                lbt: 'segment_category'
            }
        },
        onLazyRead: function(node){
            node.appendAjax(
            {
                url: _site_url+'ajax/dynatree_data',
                dataType: "json",
                data: {
                    key: node.data.key,
                    checked: $j('#hdn_seg_cats').val(),                    
                    mode: "branch", 
                    lbt: 'segment_category'
                }
            });
        },                  
        /*dnd: {
          autoExpandMS: 1000,
          preventVoidMoves: true, // Prevent dropping nodes 'before self', etc.
          onDragStart: function(node) {return true;},
          // This function is optional.
          onDragStop: function(node) {},
          onDragEnter: function(node, sourceNode) {
            // Prevent dropping a parent below another parent (only sort)
            return true
          },
          onDragOver: function(node, sourceNode, hitMode) {
            // Return false to disallow dropping this node.
            // Prevent dropping a parent below it's own child
            if(node.isDescendantOf(sourceNode)){
              return false;
            }
            // Prohibit creating childs in non-folders (only sorting allowed)
            // if( !node.isFolder && hitMode == "over" ) return "after";
          },
          onDrop: function(node, sourceNode, hitMode, ui, draggable) {
            // This function MUST be defined to enable dropping of items on the tree.
            sourceNode.move(node, hitMode);
            var currentTree = tree_elmt.dynatree("getTree").toDict();
            $j.post(_site_url+'ajax/dynatree_save', { recieved: currentTree},
               function(data) {
                 console.log(data);
            });
            // expand the drop target
            sourceNode.expand(true);
          },
          onDragLeave: function(node, sourceNode) {
            // Always called if onDragEnter was called.
          }
        },*/
        onClick: function(node, event) {
             if( node.getEventTargetType(event) == "title" )
          node.toggleSelect();
          
            if( $j(".contextMenu:visible").length > 0 )   $j(".contextMenu").hide();
        },
        onDblClick: function(node, event) {
            node.toggleSelect();
        },
          /*Bind context menu for every node when it's DOM element is created.
            We do it here, so we can also bind to lazy nodes, which do not
            exist at load-time. (abeautifulsite.net menu control does not support event delegation)*/
        onCreate: function(node, span){  
            bindContextMenu(span); 
        }
    });  
    
    $j('#tree_reload').on('click', function(){
        tree_elmt.dynatree("getTree").reload();
    })
          
});
</script>
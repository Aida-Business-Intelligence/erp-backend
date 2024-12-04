( function( $ ) {
	"use strict";
	jQuery(document).ready(function($) {
		$("body").on("click",".crm-marketing-config-tag-inner",function(){
			var id = $(this).data("id");
			$(".crm-marketing-config-tag-inner").removeClass("active");
			$(this).addClass("active");
			$(".crm-marketing-config-tab").addClass("hidden");
			$("#crm-marketing-config-tab-"+id).removeClass("hidden");
		})
		$("body").on("click",".crm-marketing-config-tag-inner a",function(e){
			e.preventDefault();
		})
		$("body").on("click",".crm-maketing-remove-all-logs",function(e){
			e.preventDefault();
			$(this).attr("disabled","disabled");
			jQuery.post(ajaxurl, {'action': 'crm_marketing_remove_all_logs',}, function(response) {
				window.location.href = window.location.href
			});
		})
		$("body").on("click",".type-query-submit",function(e){
			e.preventDefault();
			var type = $(this).closest(".bulkactions").find(".crm-filter-type").val();
			var url = $("#crm-marketing-url-list").val();
			if( type != "" ){
				window.location.href = url +"&type-filter="+type;
			}else{
				window.location.href = url;
			}
		})
		$("body").on("click",".crm-marketing-remove-options",function(e){
			e.preventDefault();
			var add_on = $(this).data("add_on");
			var url = window.location.href; 
			$(this).html("Deleting...")
			jQuery.post(ajaxurl, {'action': 'crm_marketing_remove_options',"add_on":add_on}, function(response) {
				window.location.href = window.location.href
			});
		})
		$("body").on("click",".crm-marketing-header-addnew",function(e){
			e.preventDefault();
			var key_rand = Math.floor(Math.random() * 100000);
			var content = $(this).closest(".crm-marketing-content").find(".crm-marketing-container-content-data").clone();
			content.find(".crm-data-remove").remove();
			content = content.html();
			content = '<div class="crm-marketing-row-content" data-id="'+key_rand+'">'+content+'</div>';
			content = content.replaceAll("crm_change_key",key_rand);
			content = content.replaceAll("remove_key_","");
			$(this).closest(".crm-marketing-content").find(".crm-marketing-container-content").append(content);
		})
		$("body").on("click",".crm-marketing-content-remove-row",function(e){
			e.preventDefault();
			$(this).closest(".crm-marketing-row-content").remove();
		})
		$("body").on("click",".crm-marketing-content-plus-minus",function(e){
			e.preventDefault();
			$(this).closest(".crm-marketing-row-content").remove();
		})
		$("body").on("click",".crm-add-map-field",function(e){
			e.preventDefault();
			var key_rand = $(this).closest(".crm-marketing-row-content").data("id");
			var content = $(this).closest(".crm-marketing-content").find(".data-map-field").html();
			content = '<div class="crm-martketing-map-fields-row">'+content+'</div>';
			content = content.replaceAll("crm_change_key",key_rand);
			content = content.replaceAll("remove_key_","");
			$(this).closest("div").before(content);
		})
		$("body").on("change",".crm-martketing-map-fields-row select.crm-input-sync",function(e){
			e.preventDefault();
			var value = $(this).val();
			if( value == "enter_value"){
				$(this).closest("div").find("input").val("");
				$(this).closest("div").find("input").removeClass("hidden");
			}else{
				$(this).closest("div").find("input").addClass("hidden");
				$(this).closest("div").find("input").val(value);
			}
		})
		$("body").on("click",".map-fields-action a.remove",function(e){
			e.preventDefault();
			$(this).closest(".crm-martketing-map-fields-row").remove();
		})
		$("body").on("click",".crm-marketing-tab-main li",function(e){
			e.preventDefault();
			var tab = $(this).data("id");
			var url = new URL(window.location);
			url.searchParams.set('inner_tab', tab);
			window.history.pushState({}, '', url);
			$(".crm_marketing_inner_tab").val(tab);
			$(".crm-marketing-tab-main li").removeClass("active");
			$(this).addClass("active");
			$(".crm-marketing-tab-content-inner").addClass("hidden");
			$(tab).removeClass("hidden");
		})
		$("body").on("click",".crm_marketing_sync",function(e){
			e.preventDefault();
			$(this).html("Sync...");
			var data = {
				'action': 'crm_marketing_sync',
				'id': $(this).closest("form").find(".crm_marketing_form_id").val(),
				'type': $(this).closest("form").find(".crm_marketing_type").val(),
				'add_on': $(this).closest("form").find(".crm_marketing_type_add_on").val(),
			};
			console.log(data);
			// We can also pass the url value separately from ajaxurl for front end AJAX implementations
			jQuery.post(ajaxurl, data, function(response) {
				console.log(response);
				alert(response);
				$(".crm_marketing_sync").html("Sync");
			});
		})
		$("body").on("change",".crm-marketing-method",function(e){
			e.preventDefault();
			var value = $(this).val();
			var check = false;
			var session = $(this).closest(".crm-marketing-row-content");
			var datas = $(this).closest(".crm-marketing-config-tab").find(".crm-marketing-logic").val();
			datas = JSON.parse(datas);
			$.each(datas,function(key,val){
		        if( value == key ){
		        	check = true;
		        	if(typeof(val.show) != "undefined" && val.show !== null) {
		        		var lists_class = val.show.split(" ");
		        		lists_class.push('.crm-marketing-method-tr');
		        		session.find("tr").addClass("hidden");
					   $.each( lists_class, function( k, v ) {
					   		session.find(v).removeClass("hidden");
						});
					}
					if(typeof(val.map_fields) != "undefined" && val.map_fields !== null) { 
						var options = "<option>----</option>";
						$.each( val.map_fields, function( k, v ) {
					   		options += '<option value="'+k+'">'+v+'</option>'
						});
						options +='<option value="enter_value">Custom Value</option>';
						session.find(".map-fields-key .crm-input-sync").html(options);
					}
		        }
		    })
		    if( !check ){
		    	session.find("tr").removeClass("hidden");
		    }
		})
		$("body").on("click",".crm-merge-tags",function(e){
			e.preventDefault();
			var list_fields = $(this).closest(".crm-marketing-config-tab").find(".crm-marketing-list-fields").val();
			list_fields = JSON.parse(list_fields);
			var html = '<select class="crm-marketing-list-merge-tags">';
			html += "<option>----</option>";
			$.each(list_fields,function(key,val){ 
				html += "<option value='"+key+"'>"+val+"</option>";
			})
			html +='</select>';
			$(this).closest(".crm-marketing-merge-tags-container").append(html);
		})
		$(document).mouseup(function(e) {
		    var container = $(".crm-marketing-list-merge-tags");
		    // if the target of the click isn't the container nor a descendant of the container
		    if (!container.is(e.target) && container.has(e.target).length === 0) 
		    {
		        container.remove();
		    }
		});
		$("body").on("change",".crm-marketing-list-merge-tags",function(e){ 
			e.preventDefault();
			var value = $(this).val();
			var old_value = $(this).closest(".crm-marketing-merge-tags-container").find("input").val();
			$(this).closest(".crm-marketing-merge-tags-container").find(".code-selector").val(old_value + value);
			$(this).closest(".crm-marketing-merge-tags-container").find(".crm-marketing-list-merge-tags").remove();
		})
		$("body").on("change",".crm-marketing-method-api_select input",function(e){ 
			e.preventDefault();
			var value = $(this).val();
			$(this).closest("table").find(".crm-marketing-method-api").addClass("hidden");
			$(this).closest("table").find(".crm-marketing-method-api-"+value).removeClass("hidden");
		})
	})
} )( jQuery );
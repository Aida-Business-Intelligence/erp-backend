<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<script src="<?php echo base_url('modules/api_crm/js/main.js'); ?>"></script>
<div id="wrapper">
   <div class="content">
      <div class="row">
         <div class="col-md-12">
            <div class="panel_s">
               <div class="panel-body">
			          <div class="_buttons">
                     <a href="#" onclick="new_crm_api();return false;" class="btn btn-info"><?php echo _l('new_token'); ?></a>
                  </div>
                  <div class="clearfix"></div>
                  <hr class="hr-panel-heading" />
                  <div class="clearfix"></div>
                  <div class="row">
                     <div class="col-md-12">
                         API URL: <strong><input style="width:70%; border:none;" type="text" name="" value="<?php echo site_url() ?>"></strong>
                     </div>
                  </div>
                  <hr class="hr-panel-heading">
			         <table class="apitable table dt-table">
                     <thead>
                        <th><?php echo _l('id'); ?></th>
                        <th><?php echo _l('name_api'); ?></th>
                        <th><?php echo _l('token_api'); ?></th>
                        <th><?php echo _l('options'); ?></th>
                     </thead>
                     <tbody>
                        <?php foreach($user_api as $user){ ?>
                        <tr>
                           <td><?php echo addslashes($user['id']); ?></td>
                           <td><?php echo addslashes($user['name']); ?></td>
                           <td><?php echo addslashes($user['token']); ?></td>
                           <td>
                             <a href="#" onclick="edit_crm_api(this,<?php echo addslashes($user['id']); ?>); return false" data-user="<?php echo addslashes($user['user']); ?>" data-name="<?php echo addslashes($user['name']); ?>" data-expiration_date="<?php echo addslashes($user['expiration_date']); ?>" class="btn btn-default btn-icon"><i class="fa fa-pencil-square-o"></i></a>
                             <a href="<?php echo admin_url('api_crm/delete_token/'.addslashes($user['id'])); ?>" class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>
                           </td>
                        </tr>
                        <?php } ?>
                     </tbody>
                  </table>
               </div>
            </div>
         </div>
         
      </div>
   </div>
</div>
<div class="modal fade add_api_form"  tabindex="-1" role="dialog">
   <div class="modal-dialog">
      <?php echo form_open(admin_url('api_crm/token')); ?>
      <div class="modal-content">
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">
               <span class="edit-title"><?php echo _l('edit_user_api'); ?></span>
               <span class="add-title"><?php echo _l('new_user_api'); ?></span>
            </h4>
         </div>
         <div class="modal-body">
            <div class="row">
               <div class="col-md-12">
                  <div id="additional"></div>
                  <?php echo render_input('name','name_api'); ?>
               </div>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
         </div>
      </div><!-- /.modal-content -->
      <?php echo form_close(); ?>
   </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php init_tail(); ?>
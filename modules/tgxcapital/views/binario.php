<?php defined('BASEPATH') or exit('No direct script access allowed');

?>

<style>
    
    #binario .voltaraotopo { text-align:center; }
#binario .voltaraotopo a { display:inline-block; background:#555; padding:5px 8px 4px; width:87px; font-weight:bold; font-size:1.1em; color:#fff; text-decoration:none; text-transform:uppercase; text-align:center; }
#binario .voltaraotopo a:hover { background:#000; }

#binario table#rede { margin:0 auto; width:100% !important; }
#binario table#rede tr { border:0; padding:0; }
#binario table#rede tr td { border:0; padding:5px 2px; text-align:center; vertical-align:top; }
#binario table#rede tr td span { display:block; line-height:12px; font-size:.9em; }
#binario table#rede tr td span strong { display:block; font-size:1em; text-align:center;}
#binario table#rede tr td span.lado { font-weight:bold; font-size:.9em; }
#binario table#rede tr td.inativo span.lado { color:#bbb; }
#binario table#rede tr td .alert-info { background:#eee; border:0; margin:0; }
#binario table#rede .alert.alert-info.aprovado { background:#547C95; }
#binario table#rede tr td.aleft { border-right:solid 1px #bbb; border-bottom:solid 1px #bbb; }
#binario table#rede tr td.aright { border-left:solid 1px #bbb; border-bottom:solid 1px #bbb; }
#binario table#rede tr td.bleft { border-right:solid 1px #bbb; }
#binario table#rede tr td.dbottom { border-bottom:solid 1px #bbb; }
#binario table#rede tr td div img { display:block; margin:0 auto; }
#binario table#rede tr td.inativo div img { padding:10px 5px; opacity: .7; }

#matriz table { text-align:center; border-collapse:collapse; width:100%; }
#matriz table tr td { border:0; padding:5px 2px; text-align:center; vertical-align:top; }
#matriz table tr td.inativo { padding: 10px 5px; }
#matriz table tr td div img { display:block; margin:0 auto; opacity: 1; }
#matriz table tr td.inativo div img { opacity: .7; }
#matriz table tr td.a1 { border-bottom:solid 1px #bbb; }
#matriz table tr td.a1l { border-bottom:solid 1px #bbb; border-right:solid 1px #bbb; }
#matriz table tr td.a1r { border-bottom:solid 1px #bbb; border-left:solid 1px #bbb; }
#matriz table tr td.a2 { border-left:solid 1px #bbb; }
#matriz table tr td a { color:#333; }
#matriz table tr td a:hover { color:#DD8800; text-decoration:none; }
#matriz table tr td span { display:block; padding:5px; line-height:12px; font-size:.9em; }
#matriz table tr td span strong { font-size:1em; }
#matriz table tr td table { width:100%; }
#matriz table tr td i { font-size:.65em; font-style:normal; font-weight:normal; }

    </style>

        
        
        <div class="panel panel-default">
	<div class="panel-heading">
		<h5 class="panel-title"><i class="icon-tree7 position-left"></i> Binário</h5>
	</div>
	<div class="table-responsive">
		<div id="binario">
			<?php if ($top->id != $this->user->id): ?><p class="voltaraotopo"><a href="<?=site_url('backoffice/tree/binary');?>"><strong>Topo</strong></a></p><?php endif; ?>
			<br />
			<table id="rede">
				<tr class="a1">
					<td colspan="11"></td>
					<td colspan="10"><div data-popup="tooltip" data-original-title="<?=strtoupper($top->firstname . ' ' . $top->lastname);?>"><i class="fa fa-users" aria-hidden="true"></i></td>
					<td colspan="11"></td>
				</tr>
				<tr class="a2">
					<td colspan="8"></td>
					<td colspan="8" class="aleft"></td>
					<td colspan="8" class="aright"></td>
					<td colspan="8"></td>
				</tr>
				<tr class="a2">
					<td colspan="8" class="bleft"></td>
					<td colspan="8"></td>
					<td colspan="8" class="bleft"></td>
					<td colspan="8"></td>
				</tr>
				<tr class="a2">
					<td colspan="8" class="bleft"></td>
					<td colspan="8"></td>
					<td colspan="8" class="bleft"></td>
					<td colspan="8"></td>
				</tr>
				<tr class="a1">
					<?php
					$left = get_user($top->id, 'left');
					if (!$left):
						echo '<td colspan="16" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="16" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $left->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($left->firstname . ' ' . $left->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $left->gender . '.png') . '" /></div></a></td>';
					endif;

					$right = get_user($top->id, 'right');
					if (!$right):
						echo '<td colspan="16" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="16" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $right->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($right->firstname . ' ' . $right->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $right->gender . '.png') . '" /></div></a></td>';
					endif;
					?>
				</tr>
				<tr class="a3">
					<td colspan="4"></td>
					<td colspan="4" class="aleft"></td>
					<td colspan="4" class="aright"></td>
					<td colspan="4"></td>
					<td colspan="4"></td>
					<td colspan="4" class="aleft"></td>
					<td colspan="4" class="aright"></td>
					<td colspan="4"></td>
				</tr>
				<tr class="a3">
					<td colspan="4" class="bleft"></td>
					<td colspan="4"></td>
					<td colspan="4" class="bleft"></td>
					<td colspan="4"></td>
					<td colspan="4" class="bleft"></td>
					<td colspan="4"></td>
					<td colspan="4" class="bleft"></td>
					<td colspan="4"></td>
				</tr>
				<tr class="a2">
					<?php
					$left_1 = get_user($left->id, 'left');
					if (!$left_1):
						echo '<td colspan="8" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="8" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $left_1->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($left_1->firstname . ' ' . $left_1->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $left_1->gender . '.png') . '" /></div></a></td>';
					endif;
					$left_2 = get_user($left->id, 'right');
					if (!$left_2):
						echo '<td colspan="8" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="8" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $left_2->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($left_2->firstname . ' ' . $left_2->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $left_2->gender . '.png') . '" /></div></a></td>';
					endif;

					$right_1 = get_user($right->id, 'left');
					if (!$right_1):
						echo '<td colspan="8" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="8" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $right_1->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($right_1->firstname . ' ' . $right_1->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $right_1->gender . '.png') . '" /></div></a></td>';
					endif;
					$right_2 = get_user($right->id, 'right');
					if (!$right_2):
						echo '<td colspan="8" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="8" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $right_2->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($right_2->firstname . ' ' . $right_2->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $right_2->gender . '.png') . '" /></div></a></td>';
					endif;
					?>
				</tr>
				<tr class="a4">
					<td colspan="2"></td>
					<td colspan="2" class="aleft"></td>
					<td colspan="2" class="aright"></td>
					<td colspan="2"></td>
					<td colspan="2"></td>
					<td colspan="2" class="aleft"></td>
					<td colspan="2" class="aright"></td>
					<td colspan="2"></td>
					<td colspan="2"></td>
					<td colspan="2" class="aleft"></td>
					<td colspan="2" class="aright"></td>
					<td colspan="2"></td>
					<td colspan="2"></td>
					<td colspan="2" class="aleft"></td>
					<td colspan="2" class="aright"></td>
					<td colspan="2"></td>
				</tr>
				<tr class="a4">
					<td colspan="2" class="bleft"></td>
					<td colspan="2"></td>
					<td colspan="2" class="bleft"></td>
					<td colspan="2"></td>
					<td colspan="2" class="bleft"></td>
					<td colspan="2"></td>
					<td colspan="2" class="bleft"></td>
					<td colspan="2"></td>
					<td colspan="2" class="bleft"></td>
					<td colspan="2"></td>
					<td colspan="2" class="bleft"></td>
					<td colspan="2"></td>
					<td colspan="2" class="bleft"></td>
					<td colspan="2"></td>
					<td colspan="2" class="bleft"></td>
					<td colspan="2"></td>
				</tr>
				<tr class="a3">
					<?php
					$left_1_1 = get_user($left_1->id, 'left');
					if (!$left_1_1):
						echo '<td colspan="4" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="4" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $left_1_1->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($left_1_1->firstname . ' ' . $left_1_1->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $left_1_1->gender . '.png') . '" /></div></a></td>';
					endif;
					$left_1_2 = get_user($left_1->id, 'right');
					if (!$left_1_2):
						echo '<td colspan="4" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="4" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $left_1_2->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($left_1_2->firstname . ' ' . $left_1_2->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $left_1_2->gender . '.png') . '" /></div></a></td>';
					endif;
					$left_2_1 = get_user($left_2->id, 'left');
					if (!$left_2_1):
						echo '<td colspan="4" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="4" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $left_2_1->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($left_2_1->firstname . ' ' . $left_2_1->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $left_2_1->gender . '.png') . '" /></div></a></td>';
					endif;
					$left_2_2 = get_user($left_2->id, 'right');
					if (!$left_2_2):
						echo '<td colspan="4" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="4" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $left_2_2->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($left_2_2->firstname . ' ' . $left_2_2->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $left_2_2->gender . '.png') . '" /></div></a></td>';
					endif;

					$right_1_1 = get_user($right_1->id, 'left');
					if (!$right_1_1):
						echo '<td colspan="4" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="4" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $right_1_1->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($right_1_1->firstname . ' ' . $right_1_1->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $right_1_1->gender . '.png') . '" /></div></a></td>';
					endif;
					$right_1_2 = get_user($right_1->id, 'right');
					if (!$right_1_2):
						echo '<td colspan="4" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="4" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $right_1_2->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($right_1_2->firstname . ' ' . $right_1_2->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $right_1_2->gender . '.png') . '" /></div></a></td>';
					endif;
					$right_2_1 = get_user($right_2->id, 'left');
					if (!$right_2_1):
						echo '<td colspan="4" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="4" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $right_2_1->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($right_2_1->firstname . ' ' . $right_2_1->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $right_2_1->gender . '.png') . '" /></div></a></td>';
					endif;
					$right_2_2 = get_user($right_2->id, 'right');
					if (!$right_2_2):
						echo '<td colspan="4" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="4" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $right_2_2->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($right_2_2->firstname . ' ' . $right_2_2->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $right_2_2->gender . '.png') . '" /></div></a></td>';
					endif;
					?>
				</tr>
				<tr class="a5">
					<td colspan="1"></td>
					<td colspan="1" class="aleft"></td>
					<td colspan="1" class="aright"></td>
					<td colspan="1"></td>
					<td colspan="1"></td>
					<td colspan="1" class="aleft"></td>
					<td colspan="1" class="aright"></td>
					<td colspan="1"></td>
					<td colspan="1"></td>
					<td colspan="1" class="aleft"></td>
					<td colspan="1" class="aright"></td>
					<td colspan="1"></td>
					<td colspan="1"></td>
					<td colspan="1" class="aleft"></td>
					<td colspan="1" class="aright"></td>
					<td colspan="1"></td>
					<td colspan="1"></td>
					<td colspan="1" class="aleft"></td>
					<td colspan="1" class="aright"></td>
					<td colspan="1"></td>
					<td colspan="1"></td>
					<td colspan="1" class="aleft"></td>
					<td colspan="1" class="aright"></td>
					<td colspan="1"></td>
					<td colspan="1"></td>
					<td colspan="1" class="aleft"></td>
					<td colspan="1" class="aright"></td>
					<td colspan="1"></td>
					<td colspan="1"></td>
					<td colspan="1" class="aleft"></td>
					<td colspan="1" class="aright"></td>
					<td colspan="1"></td>
				</tr>
				<tr class="a5">
					<td colspan="1" class="bleft"></td>
					<td colspan="1"></td>
					<td colspan="1" class="bleft"></td>
					<td colspan="1"></td>
					<td colspan="1" class="bleft"></td>
					<td colspan="1"></td>
					<td colspan="1" class="bleft"></td>
					<td colspan="1"></td>
					<td colspan="1" class="bleft"></td>
					<td colspan="1"></td>
					<td colspan="1" class="bleft"></td>
					<td colspan="1"></td>
					<td colspan="1" class="bleft"></td>
					<td colspan="1"></td>
					<td colspan="1" class="bleft"></td>
					<td colspan="1"></td>
					<td colspan="1" class="bleft"></td>
					<td colspan="1"></td>
					<td colspan="1" class="bleft"></td>
					<td colspan="1"></td>
					<td colspan="1" class="bleft"></td>
					<td colspan="1"></td>
					<td colspan="1" class="bleft"></td>
					<td colspan="1"></td>
					<td colspan="1" class="bleft"></td>
					<td colspan="1"></td>
					<td colspan="1" class="bleft"></td>
					<td colspan="1"></td>
					<td colspan="1" class="bleft"></td>
					<td colspan="1"></td>
					<td colspan="1" class="bleft"></td>
					<td colspan="1"></td>
				</tr>
				<tr class="a4">
					<?php
					$left_1_1_1 = get_user($left_1_1->id, 'left');
					if (!$left_1_1_1):
						echo '<td colspan="2" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="2" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $left_1_1_1->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($left_1_1_1->firstname . ' ' . $left_1_1_1->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $left_1_1_1->gender . '.png') . '" /></div></a></td>';
					endif;
					$left_1_1_2 = get_user($left_1_1->id, 'right');
					if (!$left_1_1_2):
						echo '<td colspan="2" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="2" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $left_1_1_2->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($left_1_1_2->firstname . ' ' . $left_1_1_2->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $left_1_1_2->gender . '.png') . '" /></div></a></td>';
					endif;
					$left_1_2_1 = get_user($left_1_2->id, 'left');
					if (!$left_1_2_1):
						echo '<td colspan="2" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="2" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $left_1_2_1->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($left_1_2_1->firstname . ' ' . $left_1_2_1->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $left_1_2_1->gender . '.png') . '" /></div></a></td>';
					endif;
					$left_1_2_2 = get_user($left_1_2->id, 'right');
					if (!$left_1_2_2):
						echo '<td colspan="2" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="2" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $left_1_2_2->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($left_1_2_2->firstname . ' ' . $left_1_2_2->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $left_1_2_2->gender . '.png') . '" /></div></a></td>';
					endif;
					$left_2_1_1 = get_user($left_2_1->id, 'left');
					if (!$left_2_1_1):
						echo '<td colspan="2" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="2" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $left_2_1_1->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($left_2_1_1->firstname . ' ' . $left_2_1_1->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $left_2_1_1->gender . '.png') . '" /></div></a></td>';
					endif;
					$left_2_1_2 = get_user($left_2_1->id, 'right');
					if (!$left_2_1_2):
						echo '<td colspan="2" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="2" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $left_2_1_2->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($left_2_1_2->firstname . ' ' . $left_2_1_2->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $left_2_1_2->gender . '.png') . '" /></div></a></td>';
					endif;
					$left_2_2_1 = get_user($left_2_2->id, 'left');
					if (!$left_2_2_1):
						echo '<td colspan="2" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="2" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $left_2_2_1->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($left_2_2_1->firstname . ' ' . $left_2_2_1->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $left_2_2_1->gender . '.png') . '" /></div></a></td>';
					endif;
					$left_2_2_2 = get_user($left_2_2->id, 'right');
					if (!$left_2_2_2):
						echo '<td colspan="2" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="2" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $left_2_2_2->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($left_2_2_2->firstname . ' ' . $left_2_2_2->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $left_2_2_2->gender . '.png') . '" /></div></a></td>';
					endif;

					$right_1_1_1 = get_user($right_1_1->id, 'left');
					if (!$right_1_1_1):
						echo '<td colspan="2" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="2" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $right_1_1_1->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($right_1_1_1->firstname . ' ' . $right_1_1_1->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $right_1_1_1->gender . '.png') . '" /></div></a></td>';
					endif;
					$right_1_1_2 = get_user($right_1_1->id, 'right');
					if (!$right_1_1_2):
						echo '<td colspan="2" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="2" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $right_1_1_2->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($right_1_1_2->firstname . ' ' . $right_1_1_2->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $right_1_1_2->gender . '.png') . '" /></div></a></td>';
					endif;
					$right_1_2_1 = get_user($right_1_2->id, 'left');
					if (!$right_1_2_1):
						echo '<td colspan="2" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="2" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $right_1_2_1->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($right_1_2_1->firstname . ' ' . $right_1_2_1->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $right_1_2_1->gender . '.png') . '" /></div></a></td>';
					endif;
					$right_1_2_2 = get_user($right_1_2->id, 'right');
					if (!$right_1_2_2):
						echo '<td colspan="2" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="2" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $right_1_2_2->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($right_1_2_2->firstname . ' ' . $right_1_2_2->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $right_1_2_2->gender . '.png') . '" /></div></a></td>';
					endif;
					$right_2_1_1 = get_user($right_2_1->id, 'left');
					if (!$right_2_1_1):
						echo '<td colspan="2" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="2" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $right_2_1_1->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($right_2_1_1->firstname . ' ' . $right_2_1_1->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $right_2_1_1->gender . '.png') . '" /></div></a></td>';
					endif;
					$right_2_1_2 = get_user($right_2_1->id, 'right');
					if (!$right_2_1_2):
						echo '<td colspan="2" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="2" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $right_2_1_2->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($right_2_1_2->firstname . ' ' . $right_2_1_2->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $right_2_1_2->gender . '.png') . '" /></div></a></td>';
					endif;
					$right_2_2_1 = get_user($right_2_2->id, 'left');
					if (!$right_2_2_1):
						echo '<td colspan="2" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="2" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $right_2_2_1->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($right_2_2_1->firstname . ' ' . $right_2_2_1->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $right_2_2_1->gender . '.png') . '" /></div></a></td>';
					endif;
					$right_2_2_2 = get_user($right_2_2->id, 'right');
					if (!$right_2_2_2):
						echo '<td colspan="2" class="inativo"><div data-popup="tooltip" data-original-title="VAZIO"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_inactive.png') . '" /></div></td>';
					else:
						echo '<td colspan="2" class="ativo"><a href="' . site_url('backoffice/tree/binary/' . $right_2_2_2->id) . '"><div data-popup="tooltip" data-original-title="' . strtoupper($right_2_2_2->firstname . ' ' . $right_2_2_2->lastname) . '"><img style="max-height:100px;" class="img-responsive"  alt="" src="' . base_url('assets/images/user_' . $right_2_2_2->gender . '.png') . '" /></div></a></td>';
					endif;
					?>
				</tr>
				<tr><td colspan="32"></td></tr>
			</table>
		</div>
	</div>
</div>
        
       
       




<?php init_tail(); 

function get_user($owner_id, $direction) {
    $direction = $direction.'_node';
    $tree = Binarytree::find_by_sql("SELECT * FROM `tblbinary` WHERE `userid` = '{$owner_id}' LIMIT 1")[0];
    if (($userId = $tree->{$direction})) {
        $user = User::find_by_id($userId);
        return $user;
    }

    return FALSE;
}

?>


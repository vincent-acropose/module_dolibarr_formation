<div class="fichecenter">
	<div class="fichehalfleft">
		<table class="centpercent notopnoleftnoright">
			<tbody>
				<tr>
					<td class="nobordernopadding widthpictotitle" valign="middle">
						<img src="/dolibarr/htdocs/theme/eldy/img/title_generic.png" alt="" title="" class="valignmiddle" id="pictotitle">
					</td>
					<td class="nobordernopadding" valign="middle">
						<div class="titre">
							<?php echo $langs->trans("StatisticsOfTraining"); ?>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>
<div class="fichecenter">
	<div class="fichehalfleft">
		<form method="POST">
			<input type="hidden" name="action" value="getStats">
			<table class="centpercent noborder" style="margin-top: 20px;">
				<tbody>
					<tr class="liste_titre">
						<th class="liste_titre" colspan="2">
							<?php echo $langs->trans("Filter"); ?>
						</th>
					</tr>
					<tr>
						<td align="left"><?php echo $langs->trans("Collaborateur"); ?></td>
						<td align="left">
							<?php echo $form->select_dolusers('', 'user', 1) ?>
						</td>
					</tr>
					<tr>
						<td align="left"><?php echo $langs->trans("Year"); ?></td>
						<td align="left">
							<?php echo $object->getYear(); ?>
						</td>
					</tr>
					<tr>
						<td align="center" colspan="2"><input type="submit" class="button" value=<?php echo $langs->trans("Refresh"); ?>></td>
					</tr>
				</tbody>
			</table>
		</form>
	</div>
	<div class="clearboth"></div>
	<div class="fichehalfleft">
		<table class="centpercent noborder" style="margin-top: 20px;">
			<tbody>
				<tr class="liste_titre">
					<th class="liste_titre" align="center">
						<?php echo $langs->trans("Year"); ?>
					</th>
					<th class="liste_titre" align="center">
						<?php echo $langs->trans("TrainingCost"); ?>
					</th>
					<th class="liste_titre" align="center">
						<?php echo $langs->trans("CollabCost"); ?>
					</th>
					<th class="liste_titre" align="center">
						<?php echo $langs->trans("Help"); ?>
					</th>
					<th class="liste_titre" align="center">
						<?php echo $langs->trans("TotalCost"); ?>
					</th>
				</tr>
				<tr class="oddeven">
					<td align="center">
						<?php 
							if ($year != "") echo $year;
							else echo "xxxx"; 
						?>
					</td>
					<td align="right">
						<?php echo $trainingCost; ?> €
					</td>
					<td align="right">
						<?php echo $collabCost; ?> €
					</td>
					<td align="right">
						<?php echo $help; ?> €
					</td>
					<td align="right">
						<?php echo $total; ?> €
					</td>
				</tr>
				<tr class="oddeven">
					<td align="center" colspan="5">
						<a href="documents/Stats.csv"><button class="button"><?php echo $langs->trans("CSV"); ?></button></a>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>
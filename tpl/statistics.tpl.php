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
						<th class="liste_titre" colspan="3">
							<?php echo $langs->trans("Filter"); ?>
						</th>
					</tr>
					<tr>
						<td align="left"><?php echo $langs->trans("Year"); ?></td>
						<td align="left">De
							<?php 
							echo '<select name="yearb" id="yearb" class="flat year">';
							foreach ($object->getYear() as $year) {
								if ($yearbFilter == $year['year']) {
									echo '<option value="'.$year['year'].'" selected>'.$year['year'].'</option>';
								}
								else {
									echo '<option value="'.$year['year'].'">'.$year['year'].'</option>';
								}
							}

							echo '</select>';
							?>
						</td>
						<td align="left">à
							<?php 
							echo '<select name="yearf" id="yearf" class="flat year">';
							foreach ($object->getYear() as $year) {
								if ($yearfFilter == $year['year']) {
									echo '<option value="'.$year['year'].'" selected>'.$year['year'].'</option>';
								}
								else {
									echo '<option value="'.$year['year'].'">'.$year['year'].'</option>';
								}
							}

							echo '</select>';
							?>
						</td>
					</tr>
					<tr>
						<td align="left"><?php echo $langs->trans("Status"); ?></td>
						<td align="left" colspan="2">
							<?php 
							echo '<select name="status" id="status" class="flat status">';
							echo '<option value=-1></option>';
							foreach (Formation::$TStatus as $key => $value) {
								echo '<option value="'.$key.'">'.$langs->trans($value).'</option>';
							}

							echo '</select>';
							?>
						</td>
					</tr>
					<tr>
						<td align="left"><?php echo $langs->trans("Collaborateur"); ?></td>
						<td align="left" colspan="2">
							<?php echo $form->select_dolusers($collaborator, 'user', 1) ?>
						</td>
					</tr>
					<tr>
						<td align="center" colspan="3"><input type="submit" class="button" value=<?php echo $langs->trans("Refresh"); ?>></td>
					</tr>
				</tbody>
			</table>
		</form>
		<div class="clearboth"></div>
		<table class="centpercent noborder" style="margin-top: 20px;">
			<tbody>
				<?php
				if (empty($trainings)) {
				?>
					<tr class="liste_titre">
						<th class="liste_titre" align="center">
							<?php echo $langs->trans("Date"); ?>
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
				<?php
					foreach ($object->getYear() as $year) {
				?>
						<tr class="oddeven">
							<td align="center">
								<?php echo $year['year']; ?>
							</td>
							<td align="right">
								<?php echo number_format($object->getSum($year['year'])['total_ht'], 2, ',', ''); ?> €
							</td>
							<td align="right">
								<?php echo number_format($object->getSum($year['year'])['collab'], 2, ',', ''); ?> €
							</td>
							<td align="right">
								<?php echo number_format($object->getSum($year['year'])['help'], 2, ',', ''); ?> €
							</td>
							<td align="right">
								<?php echo number_format($object->getSum($year['year'])['reste'], 2, ',', ''); ?> €
							</td>
						</tr>
				<?php
					}
				}

				else {
				?>
					<tr class="liste_titre">
						<th class="liste_titre" align="center">
							<?php echo $langs->trans("Training"); ?>
						</th>
						<th class="liste_titre" align="center">
							<?php echo $langs->trans("Date"); ?>
						</th>
						<th class="liste_titre" align="center">
							<?php echo $langs->trans("TrainingLabel"); ?>
						</th>
						<th class="liste_titre" align="center">
							<?php echo $langs->trans("Collaborator"); ?>
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
				<?php
					if ($trainings == -1) {
				?>
						<tr class="oddeven">
							<td align="center" colspan="7">
								<?php echo $langs->trans('NoTraining'); ?>
							</td>
						</tr>
				<?php
					}

					else {
						$total = 0;
						if ($collaborator == -1) {

							foreach ($trainings as $training) {

								$stt = 0;

								foreach ($training->users as $trainingUser) {
					?>
									<tr class="oddeven">
										<td align="center">
											<?php echo $training->getNomUrl(1); ?>
										</td>
										<td align="center">
											<?php echo date("d/m/Y", strtotime($training->dated)); ?>
										</td>
										<td align="center">
											<?php echo $training->label; ?>
										</td>
										<td align="right">
											<?php echo $trainingUser->login; ?>
										</td>
										<td align="right">
											<?php echo number_format($training->total_ht/sizeof($training->users), 2, ',', ''); ?> €
										</td>
										<td align="right">
											<?php 
												echo number_format($rhManager->getSalary($trainingUser->id)->salary*$training->duration, 2, ',', ''); 
											?> 
											€
										</td>
										<td align="right">
											<?php echo number_format($training->help/sizeof($training->users), 2, ',', ''); ?> €
										</td>
										<td align="right">
											<?php 
												$total_reste = $training->total_ht/sizeof($training->users) + $trainingUser->array_options['options_salaire']*$training->duration - $training->help/sizeof($training->users);
												echo number_format($total_reste, 2, ',', ''); 
											?> €
										</td>
									</tr>
					<?php
									$stt += $total_reste;
								}
								$total += $stt;
					?>
								<tr class="liste_total">
									<td align="center"><?php echo $langs->trans('SousTotal'); ?></td>
									<td colspan="7" align="right"><?php echo number_format($stt, 2, ',', '')." €"; ?></td>
								</tr>
								<tr class='oddeven'><td colspan='8'></td></tr>
					<?php
							}
					?>

						<tr class="liste_total">
							<td align="center"><?php echo $langs->trans('Total'); ?></td>
							<td colspan="7" align="right"><?php echo number_format($total, 2, ',', '')." €"; ?></td>
						</tr>

					<?php
						}
						else {
							foreach ($trainings as $training) {
					?>
								<tr class="oddeven">
									<td align="center">
										<?php echo $training->getNomUrl(1); ?>
									</td>
									<td align="center">
										<?php echo date("d/m/Y", strtotime($training->dated)); ?>
									</td>
									<td align="center">
										<?php echo $training->label; ?>
									</td>
									<td align="center">
										<?php echo $training->users[$collaborator]->login; ?>
									</td>
									<td align="right">
										<?php echo number_format($training->total_ht/sizeof($training->users), 2, ',', ''); ?> €
									</td>
									<td align="right">
										<?php 
											echo number_format($rhManager->getSalary($training->users[$collaborator]->id)->salary*$training->duration, 2, ',', ''); 
										?> 
										€
									</td>
									<td align="right">
										<?php echo number_format($training->help/sizeof($training->users), 2, ',', ''); ?> €
									</td>
									<td align="right">
										<?php 
											$total_reste = $training->total_ht/sizeof($training->users) + $training->users[$collaborator]->array_options['options_salaire']*$training->duration - $training->help/sizeof($training->users);
											echo number_format($total_reste, 2, ',', ''); 
										?> €
									</td>
								</tr>
				<?php
								$total += $total_reste;
							}
				?>
						<tr class="liste_total">
							<td align="center"><?php echo $langs->trans('Total'); ?></td>
							<td colspan="7" align="right"><?php echo number_format($total, 2, ',', '')." €"; ?></td>
						</tr>
				<?php
						}
					}
				?>

				<tr class="oddeven">
					<td align="center" colspan="7">
						<a href=<?php echo dol_buildpath('formation/documents/Stats.csv', 1); ?>><button class="button"><?php echo $langs->trans("CSV"); ?></button></a>
					</td>
				</tr>
				
				<?php
				}
				?>
			</tbody>
		</table>
	</div>
	<div class="fichehalfright text-center">
		<table class="centpercent border" style="margin-top: 20px;">
			<tbody>
				<tr>
					<?php echo '<td align="center">'.$px2 ->show().'</td>'; ?>
				</tr>
				<tr>
					<?php echo '<td align="center">'.$px1->show().'</td>'; ?>
				</tr>
			</tbody>
		</table>
	</div>
</div>
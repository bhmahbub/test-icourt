        <!-- Stats Grid -->
        <div class="lg-screen">
            <div class="row g-4 mb-4 ">
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card" data-bs-toggle="modal" data-bs-target="#modal1">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon">
                                <i class="fas fa-gavel"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">
                                    <?php echo $cases_this_month; ?>
                                </div>
                                <div class="stat-label">Disposal this Month</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="stat-card" data-bs-toggle="modal" data-bs-target="#modal2">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">
                                    <?php echo $pending_typing; ?>
                                </div>
                                <div class="stat-label">Pending Typing</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="stat-card" data-bs-toggle="modal" data-bs-target="#modal6">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon">
                                <i class="fas fa-edit"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">
                                    <?php echo $pending_correction; ?>
                                </div>
                                <div class="stat-label">Pending Correction</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="stat-card" data-bs-toggle="modal" data-bs-target="#modal3">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">
                                    <?php echo $pending_final; ?>
                                </div>
                                <div class="stat-label">Pending Final</div>
                            </div>
                        </div>
                    </div>
                </div>



            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card" data-bs-toggle="modal" data-bs-target="#modal5">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon">
                                <i class="fas fa-list"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">
                                    <?php echo $cases_last_month; ?>
                                </div>
                                <div class="stat-label">Disposal Last Month</div>
                            </div>
                        </div>
                    </div>
                </div>



                <div class="col-md-6 col-lg-3">
                    <div class="stat-card" data-bs-toggle="modal" data-bs-target="#modal4">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">
                                    <?php echo $count_pending_copy; ?>
                                </div>
                                <div class="stat-label">Pending Copy</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>


        <!-- Modal 1 Disposed this Month -->
    <div class="modal fade" id="modal1" tabindex="-1" aria-labelledby="modal1Label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal1Label">Disposed this Month</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table
                        class="table table-hover align-middle table-bordered shadow shadow-sm rounded rounded-3 mb-0">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th class="align-top">SL</th>
                                <th class="align-top text-start">Case No</th>
                                <th class="align-top text-start">Judgement</th>
                                <th class="align-top">Date</th>
                                <th class="align-top">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                    $all_this_month = $conn->query("SELECT * FROM cases WHERE MONTH(judgement_date) = MONTH(CURRENT_DATE()) AND YEAR(judgement_date) = YEAR(CURRENT_DATE()) ORDER BY judgement_date"); 
                                ?>
                            <?php if ($all_this_month && $all_this_month->num_rows > 0): $i =1; ?>
                            <?php foreach ($all_this_month as $case): ?>
                            <tr>
                                <td class="<?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  'text-success fw-bold';
                                                    } ?>">
                                    <?php echo $i; ?>
                                </td>
                                <td class="text-start <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  'text-success fw-bold';
                                                    } ?>">
                                    <?php echo htmlspecialchars($case['case_type']." ".$case['case_no']."/".$case['case_year']." (".$case['thana'].")"); ?>
                                </td>
                                <td class="text-start <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  'text-success fw-bold';
                                                    } ?>">
                                    
                                    <?php 
                                    if (strpos($case['judgement'], 'Conviction') !== false) {
                                                    echo 'Conviction';
                                                }
                                    elseif (strpos($case['judgement'], 'Acquittal') !== false) {
                                                    echo 'Acquittal';
                                                }
                                                    else{
                                                        echo  'Others';
                                                    }
                                                ?>
                                </td>
                                <td class="<?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  'text-success fw-bold';
                                                    } ?>">
                                    <?php echo date('d M, Y', strtotime($case['judgement_date'])); ?>
                                </td>
                                <td class="<?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  'text-success fw-bold';
                                                    } ?>">
                                    <?php if($case['if_typed'] === 'no' AND $case['if_corrected'] === 'no' AND $case['if_final_printed'] === 'no') {
                                                        echo  '<i class="fa-regular fa-circle-xmark text-danger"></i>';
                                                    } 
                                        elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'no' AND $case['if_final_printed'] === 'no') { echo  '<i class="fa-solid fa-check"></i>'; }

                                        elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'no') { echo  '<i class="fa-regular fa-circle-check"></i>'; }
                                        
                                        elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  '<i class="fa-solid fa-circle-check text-success"></i>';
                                                    } else {
                                                        echo  '<i class="fa-regular fa-circle-question"></i>';
                                                    } ?>
                                </td>
                            </tr>
                            <?php $i++; endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <h5 class="py-2">No case records found</h5>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 2 Pending Typing-->
    <div class="modal fade" id="modal2" tabindex="-1" aria-labelledby="modal2Label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal1Label">Pending Typing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table
                        class="table table-hover align-middle table-bordered shadow shadow-sm rounded rounded-3 mb-0">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th class="align-top">SL</th>
                                <th class="align-top text-start">Case No</th>
                                <th class="align-top text-start">Judgement</th>
                                <th class="align-top">Date</th>
                                <th class="align-top">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $all_pending_typing = $conn->query("SELECT * FROM cases WHERE if_typed = 'no' ORDER BY judgement_date"); ?>
                            <?php if ($all_pending_typing && $all_pending_typing->num_rows > 0): $i =1; ?>
                            <?php foreach ($all_pending_typing as $case): ?>
                            <tr>
                                <td>
                                    <?php echo $i; ?>
                                </td>
                                <td class="text-start">
                                    <?php echo htmlspecialchars($case['case_type']." ".$case['case_no']."/".$case['case_year']." (".$case['thana'].")"); ?>
                                </td>
                                <td class="text-start">
                                    <?php echo htmlspecialchars($case['judgement']); ?>
                                </td>
                                <td>
                                    <?php echo date('d M, Y', strtotime($case['judgement_date'])); ?>
                                </td>
                                <td class="">
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" class="action-btn edit-btn" data-bs-toggle="modal"
                                            data-bs-target="#viewModal<?= $case['id'] ?>">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                        <?php if ($_SESSION['user_type'] === 'Magistrate' || $_SESSION['user_type'] === 'Peshkar' || $_SESSION['user_type'] === 'Steno') : ?>
                                        <button type="button" class="action-btn edit-btn" data-bs-toggle="modal"
                                            data-bs-target="#editModal<?= $case['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($_SESSION['user_type'] === 'Magistrate'): ?>
                                        <button type="button" class="action-btn delete-btn" data-bs-toggle="modal"
                                            data-bs-target="#deleteModal<?= $case['id'] ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php $i++; endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <h5 class="py-2">No case records found</h5>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 3 Pending Final -->
    <div class="modal fade" id="modal3" tabindex="-1" aria-labelledby="modal3Label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal1Label">Pending Final</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table
                        class="table table-hover align-middle table-bordered shadow shadow-sm rounded rounded-3 mb-0">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th class="align-top">SL</th>
                                <th class="align-top text-start">Case No</th>
                                <th class="align-top text-start">Judgement</th>
                                <th class="align-top">Date</th>
                                <th class="align-top">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $all_pending_final = $conn->query("SELECT * FROM cases WHERE if_final_printed = 'no' ORDER BY judgement_date"); ?>
                            <?php if ($all_pending_final && $all_pending_final->num_rows > 0): $i =1; ?>
                            <?php foreach ($all_pending_final as $case): ?>
                            <tr>
                                <td class=" <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes') {
                                                        echo  'bg-success bg-opacity-25 text-success fw-bold';
                                                    } 
                                                    elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'no') {
                                                        echo  'text-success fw-bold';
                                                    }
                                                    else{
                                                        echo  'text-danger fw-bold';
                                                    }  ?>">
                                    <?php echo $i; ?>
                                </td>
                                <td class="text-start <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes') {
                                                        echo  'bg-success bg-opacity-25 text-success fw-bold';
                                                    } 
                                                    elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'no') {
                                                        echo  'text-success fw-bold';
                                                    }
                                                    else{
                                                        echo  'text-danger fw-bold';
                                                    }  ?>">
                                    <?php echo htmlspecialchars($case['case_type']." ".$case['case_no']."/".$case['case_year']." (".$case['thana'].")"); ?>
                                </td>
                                <td class="text-start <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes') {
                                                        echo  'bg-success bg-opacity-25 text-success fw-bold';
                                                    } 
                                                    elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'no') {
                                                        echo  'text-success fw-bold';
                                                    }
                                                    else{
                                                        echo  'text-danger fw-bold';
                                                    }  ?>">
                                    <?php echo htmlspecialchars($case['judgement']); ?>
                                </td>
                                <td class="<?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes') {
                                                        echo  'bg-success bg-opacity-25 text-success fw-bold';
                                                    } 
                                                    elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'no') {
                                                        echo  'text-success fw-bold';
                                                    }
                                                    else{
                                                        echo  'text-danger fw-bold';
                                                    }  ?>">
                                    <?php echo date('d M, Y', strtotime($case['judgement_date'])); ?>
                                </td>
                                <td class="<?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes') {
                                                        echo  'bg-success bg-opacity-25';
                                                    } 
                                                     ?>">
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" class="action-btn edit-btn" data-bs-toggle="modal"
                                            data-bs-target="#viewModal<?= $case['id'] ?>">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                        <?php if ($_SESSION['user_type'] === 'Magistrate' || $_SESSION['user_type'] === 'Peshkar' || $_SESSION['user_type'] === 'Steno') : ?>
                                        <button type="button" class="action-btn edit-btn" data-bs-toggle="modal"
                                            data-bs-target="#editModal<?= $case['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($_SESSION['user_type'] === 'Magistrate'): ?>
                                        <button type="button" class="action-btn delete-btn" data-bs-toggle="modal"
                                            data-bs-target="#deleteModal<?= $case['id'] ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php $i++; endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <h5 class="py-2">No case records found</h5>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 4 Pending copy -->
    <div class="modal fade" id="modal4" tabindex="-1" aria-labelledby="modal4Label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal1Label">Pending Copy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table
                        class="table table-hover align-middle table-bordered shadow shadow-sm rounded rounded-3 mb-0">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th class="align-top">SL</th>
                                <th class="align-top text-start">Case No</th>
                                <th class="align-top text-start">Judgement</th>
                                <th class="align-top">Date</th>
                                <th class="align-top">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $all_pending_copy = $conn->query("SELECT * FROM cases WHERE applied_for_copy = 'yes' AND supplied_copy = 'no' ORDER BY judgement_date"); ?>
                            <?php if ($all_pending_copy && $all_pending_copy->num_rows > 0): $i =1; ?>
                            <?php foreach ($all_pending_copy as $case): ?>
                            <tr>
                                <td class=" <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes') {
                                                        echo  'bg-success bg-opacity-25 text-success fw-bold';
                                                    } 
                                                    elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'no') {
                                                        echo  'text-success fw-bold';
                                                    }
                                                    else{
                                                        echo  'text-danger fw-bold';
                                                    }  ?>">
                                    <?php echo $i; ?>
                                </td>
                                <td class="text-start <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes') {
                                                        echo  'bg-success bg-opacity-25 text-success fw-bold';
                                                    } 
                                                    elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'no') {
                                                        echo  'text-success fw-bold';
                                                    }
                                                    else{
                                                        echo  'text-danger fw-bold';
                                                    }  ?>">
                                    <?php echo htmlspecialchars($case['case_type']." ".$case['case_no']."/".$case['case_year']." (".$case['thana'].")"); ?>
                                </td>
                                <td class="text-start <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes') {
                                                        echo  'bg-success bg-opacity-25 text-success fw-bold';
                                                    } 
                                                    elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'no') {
                                                        echo  'text-success fw-bold';
                                                    }
                                                    else{
                                                        echo  'text-danger fw-bold';
                                                    }  ?>">
                                    <?php echo htmlspecialchars($case['judgement']); ?>
                                </td>
                                <td class=" <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes') {
                                                        echo  'bg-success bg-opacity-25 text-success fw-bold';
                                                    } 
                                                    elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'no') {
                                                        echo  'text-success fw-bold';
                                                    }
                                                    else{
                                                        echo  'text-danger fw-bold';
                                                    }  ?>">
                                    <?php echo date('d M, Y', strtotime($case['judgement_date'])); ?>
                                </td>
                                <td class="<?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes') {
                                                        echo  'bg-success bg-opacity-25';
                                                    } 
                                                      ?>">
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" class="action-btn edit-btn" data-bs-toggle="modal"
                                            data-bs-target="#viewModal<?= $case['id'] ?>">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                        <?php if ($_SESSION['user_type'] === 'Magistrate' || $_SESSION['user_type'] === 'Peshkar' || $_SESSION['user_type'] === 'Steno') : ?>
                                        <button type="button" class="action-btn edit-btn" data-bs-toggle="modal"
                                            data-bs-target="#editModal<?= $case['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($_SESSION['user_type'] === 'Magistrate'): ?>
                                        <button type="button" class="action-btn delete-btn" data-bs-toggle="modal"
                                            data-bs-target="#deleteModal<?= $case['id'] ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php $i++; endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <h5 class="py-2">No case records found</h5>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 5 Disposed Last Month -->
    <div class="modal fade" id="modal5" tabindex="-1" aria-labelledby="modal5Label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal5Label">Disposed Last Month</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table id="sortableTable"
                        class="table table-hover align-middle table-bordered shadow shadow-sm rounded rounded-3 mb-0">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th class="align-top">SL</th>
                                <th class="align-top text-start">Case No</th>
                                <th class="align-top text-start">Judgement</th>
                                <th class="align-top">Date</th>
                                <th class="align-top">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $all_last_month = $conn->query("SELECT * FROM cases WHERE MONTH(judgement_date) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(judgement_date) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH) ORDER BY judgement_date"); ?>

                            <?php if ($all_last_month && $all_last_month->num_rows > 0): $i =1; ?>
                            <?php foreach ($all_last_month as $case): ?>
                            <tr>
                                <td class="<?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  'text-success fw-bold';
                                                    } ?>">
                                    <?php echo $i; ?>
                                </td>
                                <td class="text-start <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  'text-success fw-bold';
                                                    } ?>">
                                    <?php echo htmlspecialchars($case['case_type']." ".$case['case_no']."/".$case['case_year']." (".$case['thana'].")"); ?>
                                </td>
                                <td class="text-start <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  'text-success fw-bold';
                                                    } ?>">
                                    <?php 
                                    if (strpos($case['judgement'], 'Conviction') !== false) {
                                                    echo 'Conviction';
                                                }
                                    elseif (strpos($case['judgement'], 'Acquittal') !== false) {
                                                    echo 'Acquittal';
                                                }
                                                    else{
                                                        echo  'Others';
                                                    }
                                                ?>
                                </td>
                                <td class="<?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  'text-success fw-bold';
                                                    } ?>">
                                    <?php echo date('d M, Y', strtotime($case['judgement_date'])); ?>
                                </td>
                                <td class="<?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  'text-success fw-bold';
                                                    } ?>">
                                    <?php if($case['if_typed'] === 'no' AND $case['if_corrected'] === 'no' AND $case['if_final_printed'] === 'no') {
                                                        echo  '<i class="fa-regular fa-circle-xmark text-danger"></i>';
                                                    } 
                                        elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'no' AND $case['if_final_printed'] === 'no') { echo  '<i class="fa-solid fa-check"></i>'; }

                                        elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'no') { echo  '<i class="fa-regular fa-circle-check"></i>'; }
                                        
                                        elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  '<i class="fa-solid fa-circle-check text-success"></i>';
                                                    } else {
                                                        echo  '<i class="fa-regular fa-circle-question"></i>';
                                                    } ?>
                                </td>
                            </tr>
                            <?php $i++; endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5">

                                    <h5 class="py-2">No case records found</h5>


                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal 6 Pending Correction-->
    <div class="modal fade" id="modal6" tabindex="-1" aria-labelledby="modal6Label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal6Label">Pending Correction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table id="sortableTable"
                        class="table table-hover align-middle table-bordered shadow shadow-sm rounded rounded-3 mb-0">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th class="align-top">SL</th>
                                <th class="align-top text-start">Case No</th>
                                <th class="align-top text-start">Judgement</th>
                                <th class="align-top">Date</th>
                                <th class="align-top">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $all_pending_correction = $conn->query("SELECT * FROM cases WHERE if_typed = 'yes' AND if_corrected = 'no' ORDER BY judgement_date"); ?>
                            <?php if ($all_pending_correction && $all_pending_correction->num_rows > 0): $i =1; ?>
                            <?php foreach ($all_pending_correction as $case): ?>
                            <tr>
                                <td>
                                    <?php echo $i; ?>
                                </td>
                                <td class="text-start">
                                    <?php echo htmlspecialchars($case['case_type']." ".$case['case_no']."/".$case['case_year']." (".$case['thana'].")"); ?>
                                </td>
                                <td class="text-start">
                                    <?php echo htmlspecialchars($case['judgement']); ?>
                                </td>
                                <td>
                                    <?php echo date('d M, Y', strtotime($case['judgement_date'])); ?>
                                </td>

                                <td class="">
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" class="action-btn edit-btn" data-bs-toggle="modal"
                                            data-bs-target="#viewModal<?= $case['id'] ?>">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                        <?php if ($_SESSION['user_type'] === 'Magistrate' || $_SESSION['user_type'] === 'Peshkar' || $_SESSION['user_type'] === 'Steno') : ?>
                                        <button type="button" class="action-btn edit-btn" data-bs-toggle="modal"
                                            data-bs-target="#editModal<?= $case['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($_SESSION['user_type'] === 'Magistrate'): ?>
                                        <button type="button" class="action-btn delete-btn" data-bs-toggle="modal"
                                            data-bs-target="#deleteModal<?= $case['id'] ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>

                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php $i++; endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <h5 class="py-2">No case records found</h5>

                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
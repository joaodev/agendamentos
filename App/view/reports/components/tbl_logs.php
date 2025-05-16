<?php if ($this->view->isAdmin || $this->acl($_SESSION['ROLE'], $this->resourceCodes('view'), $this->moduleCodes('tasks'))): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-hover" id="table_logs" style="width: 100%;">
            <thead>
                <tr class="text-center" style="height: 30px;">
                    <th style="font-size: 14px;">Data</th>
                    <th style="font-size: 14px;">Horário</th>
                    <th style="font-size: 14px;">Usuário</th>
                    <th style="font-size: 14px;">Ação</th>
                    <th style="font-size: 14px;">Situação</th>
                    <th style="font-size: 14px;">IP</th>
                    <th style="font-size: 14px;">Dispositivo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $entity): 
                    $expData = explode(" ", $this->formatDateTime($entity['log_date']));
                    ?>
                    <tr class="text-center">
                        <td style="font-size: 15px">
                            <?php echo $expData[0]; ?>
                        </td>
                        <td style="font-size: 15px">
                            <?php echo $expData[1]; ?>
                        </td>
                        <td style="font-size: 15px">
                            <?php echo $entity['username']; ?>
                        </td>
                        <td style="font-size: 15px;">
                            <?php 
                            $pos = strpos($entity['log_action'], 'REQUISIÇÃO BLOQUEADA:');
                            if ($pos === false):
                                echo $entity['log_action'];
                            else:
                                echo 'Requisiçao bloqueada!';
                            endif;
                            ?>
                        </td>
                        <td style="font-size: 15px">
                            <?php echo $entity['log_status']; ?>
                        </td>
                        <td style="font-size: 15px">
                            <?php echo $entity['log_ip']; ?>
                        </td>
                        <td style="font-size: 15px">
                            <?php echo $entity['log_user_agent']; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script type="text/javascript">
        $(document).ready(function() {
            
            let buttonCommon = {
                exportOptions: {
                    format: {
                        body: function ( data, row, column ) {
                            return  data;
                        }
                    }
                }
            };	

            $('#table_logs').DataTable({
                "sDom": 'flBtip',
                searching: true,
                buttons: [
                    $.extend( true, {}, buttonCommon, {
                        extend: 'excel',
                        title: 'Relatório de Logs do Sistema',
                    })
                ],
                order: [ [0, "<?php echo $this->view->orderType; ?>"] ],
                columnDefs: [
                    {type: 'date-eu', targets: [0]},
                ],
                responsive: true,
                info: true,
                processing: true,
                scrollCollapse: true,
                paging: true,
                "pageLength": 25,
                "language": {
                    "sEmptyTable": "Nenhum registro encontrado",
                    "sInfo": "Mostrando de _START_ até _END_ de _TOTAL_ registros",
                    "sInfoEmpty": "Mostrando 0 até 0 de 0 registros",
                    "sInfoFiltered": "(Filtrados de _MAX_ registros)",
                    "sInfoPostFix": "",
                    "sInfoThousands": ".",
                    "sLengthMenu": "_MENU_ &nbsp;",
                    "sLoadingRecords": "Carregando...",
                    "sProcessing": "Processando...",
                    "sZeroRecords": "Nenhum registro encontrado",
                    "sSearch": "Filtrar",
                    "oPaginate": {
                        "sNext": "Próximo",
                        "sPrevious": "Anterior",
                        "sFirst": "Primeiro",
                        "sLast": "Último"
                    },
                    "oAria": {
                        "sSortAscending": ": Ordenar colunas de forma ascendente",
                        "sSortDescending": ": Ordenar colunas de forma descendente"
                    }
                }
            });
        });
    </script>
<?php endif; ?>
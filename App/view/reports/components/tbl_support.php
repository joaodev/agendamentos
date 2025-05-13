<?php if ($this->view->isAdmin || $this->acl($_SESSION['ROLE'], $this->resourceCodes('view'), $this->moduleCodes('support'))): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-hover" id="table_support" style="width: 100%;">
            <thead>
                <tr class="text-center" style="height: 30px;">
                    <th style="font-size: 14px;">Código</th>
                    <th style="font-size: 14px;">Data</th>
                    <th style="font-size: 14px;">Horário</th>
                    <th style="font-size: 14px;">Assunto</th>
                    <th style="font-size: 14px;">Situação</th>
                    <th style="display: none;">Descrição</th>
                    <th style="display: none;">Cadastro</th>
                    <th style="display: none;">Última Atualização</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $entity): ?>
                    <tr class="text-center" style="cursor: pointer;" 
                        onclick="openModalDetails('<?php echo $entity['id']; ?>');">
                        <td style="font-size: 15px">
                            <?php echo $entity['id']; ?>
                        </td>
                        <td style="font-size: 15px">
                            <?php echo $this->formatDateTime($entity['created_at']); ?>
                        </td>
                        <td style="font-size: 15px">
                            <?php echo substr(substr($entity['created_at'], 11), 0, 5); ?>
                        </td>
                        <td style="font-size: 15px">
                            <?php echo $entity['title']; ?>
                        </td>
                        <td style="font-size: 15px">
                            <?php if ($entity['status'] == '1'): ?>
                                Em Aberto
                            <?php endif; ?>
                            <?php if ($entity['status'] == '2'): ?>
                                Em Atendimento
                            <?php endif; ?>
                            <?php if ($entity['status'] == '3'): ?>
                                Finalizado
                            <?php endif; ?>
                            <?php if ($entity['status'] == '4'): ?>
                                Fechado
                            <?php endif; ?>
                        </td>
                        <td style="display: none;">
                            <?php echo $entity['description']; ?>   
                        </td> 
                        <td style="display: none;">
                            <?php echo $this->formatDateTime($entity['created_at']); ?>
                        </td> 
                        <td style="display: none;">
                            <?php echo $this->formatDateTime($entity['updated_at']); ?>
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

            $('#table_support').DataTable({
                "sDom": 'flBtip',
                searching: true,
                buttons: [
                    $.extend( true, {}, buttonCommon, {
                        extend: 'excel',
                        title: 'Relatório de Chamados',
                    })
                ],
                order: [ [0, "<?php echo $this->view->orderType; ?>"] ],
                columnDefs: [
                    {type: 'date-eu', targets: [1]},
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
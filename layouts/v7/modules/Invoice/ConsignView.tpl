{strip}

    {assign var=FACTURA value=$RECORD->getFactura()}


    {assign var=CUSTOMER value=$FACTURA["customer"]}
    <script type="text/javascript">
        var resolutions ={$RECORD->getResolutions()|json_encode};

        var res_actual = 0;
        function elegir() {
            if ($("#resolution").val()) {
                res_actual = $("#resolution")[0].selectedIndex - 1;
                $("#number").val(resolutions[res_actual].resolution_tks_next);
                $("#prefix").val(resolutions[res_actual].resolution_tks_prefix);
                $("#factura").show();

            }
        }
    </script>
    <div class="left-block col-lg-12">
        <form class="form-horizontal recordEditView" id="Factura" name="edit" method="post" action="index.php" enctype="multipart/form-data">
            {* Module Summary View*}
            <div class="summaryView">
                <div class="summaryViewFields" id="factura">

                    <div class="row">
                        <div class="col-md-3">
                        </div>
                        <div class="col-md-6">
                            <div class="panel panel-primary">
                                <div class="panel-heading">Resolución</div>
                                <div class="panel-body">
                                    <div class="col-xs-12">
                                        <div class="form-group">
                                            <select id="resolution" class="form-control" name="resolution" onchange="elegir();">
                                                <option id="">Seleccione la Resolución (Serie de Facturación)</option>
                                                {foreach item=RESOLUTION from=$RECORD->getResolutions()}
                                                    <option value="{$RESOLUTION["resolution_tks_resolution"]}">
                                                        {$RESOLUTION['resolution_tks_resolution']}  Serie: {$RESOLUTION['resolution_tks_prefix']} Desde: {$RESOLUTION['resolution_tks_from']} - Hasta: {$RESOLUTION['resolution_tks_to']} - Siguiente: {$RESOLUTION['resolution_tks_next']}
                                                    </option>
                                                {/foreach}
                                            </select>
                                        </div>
                                    </div>
                                    <input type="hidden" name="module" value="Invoice" />
                                    <input type="hidden" name="action" value="Consign" />
                                    <input type="hidden" name="record" id="recordId" value="{$RECORD->getId()}" />
                                    <input type="hidden" name="defaultCallDuration" value="5" />
                                    <input type="hidden" name="defaultOtherEventDuration" value="5" />
                                    <input type="hidden" name="appName" value="&app=INVENTORY" />
                                    <div class="form-row">
                                        <div class="col-xs-2">
                                            <div class="form-group">
                                                <label for="prefix">Prefijo</label>
                                                <input type="text" class="form-control text-right" style="background: #FFFFFF; cursor: default" id="prefix" name="prefix" readonly>
                                            </div>
                                        </div>
                                        <div class="col-xs-6">
                                            <div class="form-group">
                                                <label for="number">Número</label>
                                                <input type="text" class="form-control" style="background: #FFFFFF; cursor: default" id="number" name="number" readonly>
                                            </div>
                                        </div>
                                        <div class="col-xs-4">
                                            <div class="btn-group" role="group">
                                                <label>&nbsp;</label>
                                                <button type="submit" class="btn btn-primary alignCenter">Generar Factura Electrónica</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        {* Module Summary View Ends Here*}

        {* Summary View Documents Widget*}
        {if $DOCUMENT_WIDGET_MODEL}
            <div class="summaryWidgetContainer">
                <div class="widgetContainer_documents" data-url="{$DOCUMENT_WIDGET_MODEL->getUrl()}" data-name="{$DOCUMENT_WIDGET_MODEL->getLabel()}">
                    <div class="widget_header clearfix">
                        <input type="hidden" name="relatedModule" value="{$DOCUMENT_WIDGET_MODEL->get('linkName')}" />
                        <span class="toggleButton pull-left"><i class="fa fa-angle-down"></i>&nbsp;&nbsp;</span>
                        <h3 class="display-inline-block pull-left">{vtranslate($DOCUMENT_WIDGET_MODEL->getLabel(),$MODULE_NAME)}</h3>

                        {if $DOCUMENT_WIDGET_MODEL->get('action')}
                            {assign var=PARENT_ID value=$RECORD->getId()}
                            <div class="pull-right">
                                <div class="dropdown">
                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                        <span class="fa fa-plus" title="{vtranslate('LBL_NEW_DOCUMENT', $MODULE_NAME)}"></span>&nbsp;{vtranslate('LBL_NEW_DOCUMENT', 'Documents')}&nbsp; <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li class="dropdown-header"><i class="fa fa-upload"></i> {vtranslate('LBL_FILE_UPLOAD', 'Documents')}</li>
                                        <li id="VtigerAction">
                                            <a href="javascript:Documents_Index_Js.uploadTo('Vtiger',{$PARENT_ID},'{$MODULE_NAME}')">
                                                <img style="  margin-top: -3px;margin-right: 4%;" title="Vtiger" alt="Vtiger" src="layouts/v7/skins//images/Vtiger.png">
                                                {vtranslate('LBL_TO_SERVICE', 'Documents', {vtranslate('LBL_VTIGER', 'Documents')})}
                                            </a>
                                        </li>
                                        <li role="separator" class="divider"></li>
                                        <li class="dropdown-header"><i class="fa fa-link"></i> {vtranslate('LBL_LINK_EXTERNAL_DOCUMENT', 'Documents')}</li>
                                        <li id="shareDocument"><a href="javascript:Documents_Index_Js.createDocument('E',{$PARENT_ID},'{$MODULE_NAME}')">&nbsp;<i class="fa fa-external-link"></i>&nbsp;&nbsp; {vtranslate('LBL_FROM_SERVICE', 'Documents', {vtranslate('LBL_FILE_URL', 'Documents')})}</a></li>
                                        <li role="separator" class="divider"></li>
                                        <li id="createDocument"><a href="javascript:Documents_Index_Js.createDocument('W',{$PARENT_ID},'{$MODULE_NAME}')"><i class="fa fa-file-text"></i> {vtranslate('LBL_CREATE_NEW', 'Documents', {vtranslate('SINGLE_Documents', 'Documents')})}</a></li>
                                    </ul>
                                </div>
                            </div>
                        {/if}
                    </div>
                    <div class="widget_contents">

                    </div>
                </div>
            </div>
        {/if}
        {* Summary View Documents Widget Ends Here*}

    </div>

    <div class="middle-block col-lg-7">

        {* Summary View Related Activities Widget*}
        <div id="relatedActivities">
            {$RELATED_ACTIVITIES}
        </div>
        {* Summary View Related Activities Widget Ends Here*}

        {* Summary View Comments Widget*}
        {if $COMMENTS_WIDGET_MODEL}
            <div class="summaryWidgetContainer">
                <div class="widgetContainer_comments" data-url="{$COMMENTS_WIDGET_MODEL->getUrl()}" data-name="{$COMMENTS_WIDGET_MODEL->getLabel()}">
                    <div class="widget_header">
                        <input type="hidden" name="relatedModule" value="{$COMMENTS_WIDGET_MODEL->get('linkName')}" />
                        <h3 class="display-inline-block">{vtranslate($COMMENTS_WIDGET_MODEL->getLabel(),$MODULE_NAME)}</h3>
                    </div>
                    <div class="widget_contents">
                    </div>
                </div>
            </div>
        {/if}
        {* Summary View Comments Widget Ends Here*}

    </div>
{/strip}
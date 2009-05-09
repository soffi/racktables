<?php
/*
*
*  This file implements generic navigation for RackTables.
*
*/

$page = array();
$tab = array();
$trigger = array();
$ophandler = array();
$tabhandler = array();
$tabextraclass = array();
$delayauth = array();
$tabrev = array(); //tabs to show when not in head revision

$page['index']['title'] = 'Main page';
$page['index']['handler'] = 'renderIndex';
$tabrev['index'] = array('default');
$page['rackspace']['title'] = 'Rackspace';
$page['rackspace']['parent'] = 'index';
$tab['rackspace']['default'] = 'Browse';
$tab['rackspace']['edit'] = 'Manage rows';
$tab['rackspace']['history'] = 'History';
$tabrev['rackspace'] = array('default', 'history');
$tabhandler['rackspace']['default'] = 'renderRackspace';
$tabhandler['rackspace']['edit'] = 'renderRackspaceRowEditor';
$tabhandler['rackspace']['history'] = 'renderRackspaceHistory';
$ophandler['rackspace']['edit']['addRow'] = 'addRow';
$ophandler['rackspace']['edit']['updateRow'] = 'updateRow';
$ophandler['rackspace']['edit']['delete'] = 'deleteRow';

$page['depot']['parent'] = 'index';
$page['depot']['title'] = 'Objects';
$tab['depot']['default'] = 'Browse';
$tab['depot']['addmore'] = 'Add more';
$tabrev['depot'] = array('default');
$tabhandler['depot']['default'] = 'renderDepot';
$tabhandler['depot']['addmore'] = 'renderAddMultipleObjectsForm';
$ophandler['depot']['addmore']['addObjects'] = 'addMultipleObjects';
$ophandler['depot']['addmore']['addLotOfObjects'] = 'addLotOfObjects';
$ophandler['depot']['default']['deleteObject'] = 'deleteObject';

$page['row']['bypass'] = 'row_id';
$page['row']['bypass_type'] = 'uint';
$page['row']['parent'] = 'rackspace';
$tab['row']['default'] = 'View';
$tab['row']['newrack'] = 'Add new rack';
$tab['row']['tagroller'] = 'Tag roller';
$tab['row']['history'] = 'History';
$tabrev['row'] = array('default');
$tabhandler['row']['default'] = 'renderRow';
$tabhandler['row']['newrack'] = 'renderNewRackForm';
$tabhandler['row']['tagroller'] = 'renderTagRollerForRow';
$tabhandler['row']['history'] = 'renderHistoryForAnything';
$ophandler['row']['tagroller']['rollTags'] = 'rollTags';
$ophandler['row']['newrack']['addRack'] = 'addRack';

$page['rack']['bypass'] = 'rack_id';
$page['rack']['bypass_type'] = 'uint';
$page['rack']['parent'] = 'row';
$tab['rack']['default'] = 'View';
$tab['rack']['edit'] = 'Properties';
$tab['rack']['design'] = 'Design';
$tab['rack']['problems'] = 'Problems';
$tab['rack']['tags'] = 'Tags';
$tab['rack']['files'] = 'Files';
$tab['rack']['history'] = 'History';
$tabrev['rack'] = array('default');
$tabhandler['rack']['default'] = 'renderRackPage';
$tabhandler['rack']['edit'] = 'renderEditRackForm';
$tabhandler['rack']['design'] = 'renderRackDesign';
$tabhandler['rack']['problems'] = 'renderRackProblems';
$tabhandler['rack']['tags'] = 'renderEntityTags';
$tabhandler['rack']['files'] = 'renderFilesForEntity';
$tabhandler['rack']['history'] = 'renderHistoryForAnything';
$trigger['rack']['tags'] = 'trigger_tags';
$ophandler['rack']['design']['updateRack'] = 'updateRackDesign';
$ophandler['rack']['problems']['updateRack'] = 'updateRackProblems';
$ophandler['rack']['edit']['updateRack'] = 'updateRack';
$ophandler['rack']['edit']['deleteRack'] = 'deleteRack';
$ophandler['rack']['tags']['saveTags'] = 'saveEntityTags';
$ophandler['rack']['files']['addFile'] = 'addFileToEntity';
$ophandler['rack']['files']['linkFile'] = 'linkFileToEntity';
$ophandler['rack']['files']['unlinkFile'] = 'unlinkFile';

$page['object']['bypass'] = 'object_id';
$page['object']['bypass_type'] = 'uint';
$page['object']['parent'] = 'depot';
$tab['object']['default'] = 'View';
$tab['object']['edit'] = 'Properties';
$tab['object']['rackspace'] = 'Rackspace';
$tab['object']['ports'] = 'Ports';
$tab['object']['ipv4'] = 'IPv4';
$tab['object']['nat4'] = 'NATv4';
$tab['object']['livevlans'] = 'Live VLANs';
$tab['object']['snmpportfinder'] = 'SNMP port finder';
$tab['object']['editrspvs'] = 'RS pools';
$tab['object']['lvsconfig'] = 'keepalived.conf';
$tab['object']['autoports'] = 'AutoPorts';
$tab['object']['tags'] = 'Tags';
$tab['object']['files'] = 'Files';
$tab['object']['history'] = 'History';
$tabrev['object'] = array('default');
$tabhandler['object']['default'] = 'renderRackObject';
$tabhandler['object']['edit'] = 'renderEditObjectForm';
$tabhandler['object']['rackspace'] = 'renderRackSpaceForObject';
$tabhandler['object']['ports'] = 'renderPortsForObject';
$tabhandler['object']['ipv4'] = 'renderIPv4ForObject';
$tabhandler['object']['nat4'] = 'renderNATv4ForObject';
$tabhandler['object']['livevlans'] = 'renderVLANMembership';
$tabhandler['object']['snmpportfinder'] = 'renderSNMPPortFinder';
$tabhandler['object']['lvsconfig'] = 'renderLVSConfig';
$tabhandler['object']['autoports'] = 'renderAutoPortsForm';
$tabhandler['object']['tags'] = 'renderEntityTags';
$tabhandler['object']['files'] = 'renderFilesForEntity';
$tabhandler['object']['editrspvs'] = 'renderObjectSLB';
$tabhandler['object']['history'] = 'renderHistoryForAnything';
$tabextraclass['object']['snmpportfinder'] = 'attn';
$tabextraclass['object']['autoports'] = 'attn';
$trigger['object']['ipv4'] = 'trigger_ipv4';
$trigger['object']['nat4'] = 'trigger_natv4';
$trigger['object']['livevlans'] = 'trigger_livevlans';
$trigger['object']['snmpportfinder'] = 'trigger_snmpportfinder';
$trigger['object']['editrspvs'] = 'trigger_isloadbalancer';
$trigger['object']['lvsconfig'] = 'trigger_isloadbalancer';
$trigger['object']['autoports'] = 'trigger_autoports';
$trigger['object']['tags'] = 'trigger_tags';
$ophandler['object']['rackspace']['updateObjectAllocation'] = 'updateObjectAllocation';
$ophandler['object']['ports']['addPort'] = 'addPortForObject';
$ophandler['object']['ports']['delPort'] = 'delPortFromObject';
$ophandler['object']['ports']['editPort'] = 'editPortForObject';
$ophandler['object']['ports']['linkPort'] = 'linkPortForObject';
$ophandler['object']['ports']['unlinkPort'] = 'unlinkPortForObject';
$ophandler['object']['ports']['addMultiPorts'] = 'addMultiPorts';
$ophandler['object']['ports']['useup'] = 'useupPort';
$ophandler['object']['ipv4']['updIPv4Allocation'] = 'updIPv4Allocation';
$ophandler['object']['ipv4']['addIPv4Allocation'] = 'addIPv4Allocation';
$ophandler['object']['ipv4']['delIPv4Allocation'] = 'delIPv4Allocation';
$ophandler['object']['edit']['clearSticker'] = 'clearSticker';
$ophandler['object']['edit']['update'] = 'updateObject';
$ophandler['object']['nat4']['addNATv4Rule'] = 'addPortForwarding';
$ophandler['object']['nat4']['delNATv4Rule'] = 'delPortForwarding';
$ophandler['object']['nat4']['updNATv4Rule'] = 'updPortForwarding';
$ophandler['object']['livevlans']['setPortVLAN'] = 'setPortVLAN';
$ophandler['object']['autoports']['generate'] = 'generateAutoPorts';
$ophandler['object']['tags']['saveTags'] = 'saveEntityTags';
$ophandler['object']['files']['addFile'] = 'addFileToEntity';
$ophandler['object']['files']['linkFile'] = 'linkFileToEntity';
$ophandler['object']['files']['unlinkFile'] = 'unlinkFile';
$ophandler['object']['editrspvs']['addLB'] = 'addLoadBalancer';
$ophandler['object']['editrspvs']['delLB'] = 'deleteLoadBalancer';
$ophandler['object']['editrspvs']['updLB'] = 'updateLoadBalancer';
$ophandler['object']['lvsconfig']['submitSLBConfig'] = 'submitSLBConfig';
$ophandler['object']['snmpportfinder']['querySNMPData'] = 'querySNMPData';
$ajaxhandler['object']['ports']['getObjectsEmptyPorts'] = 'getObjectsEmptyPorts';
$ajaxhandler['object']['ports']['getEmptyPorts'] = 'getEmptyPorts';
$ajaxhandler['object']['nat4']['getObjectsInet4List'] = 'getObjectsInet4List';
$delayauth['object']['livevlans']['setPortVLAN'] = TRUE;

$page['ipv4space']['title'] = 'IPv4 space';
$page['ipv4space']['parent'] = 'index';
$tab['ipv4space']['default'] = 'Browse';
$tab['ipv4space']['newrange'] = 'Manage';
$tabrev['ipv4space'] = array('default');
$tabhandler['ipv4space']['default'] = 'renderIPv4Space';
$tabhandler['ipv4space']['newrange'] = 'renderIPv4SpaceEditor';
$ophandler['ipv4space']['newrange']['addIPv4Prefix'] = 'addIPv4Prefix';
$ophandler['ipv4space']['newrange']['delIPv4Prefix'] = 'delIPv4Prefix';
$ophandler['ipv4space']['newrange']['updIPv4Prefix'] = 'updIPv4Prefix';

$page['ipv4net']['parent'] = 'ipv4space';
$page['ipv4net']['bypass'] = 'id';
$page['ipv4net']['bypass_type'] = 'uint';
$tab['ipv4net']['default'] = 'Browse';
$tab['ipv4net']['properties'] = 'Properties';
$tab['ipv4net']['liveptr'] = 'Live PTR';
$tab['ipv4net']['tags'] = 'Tags';
$tab['ipv4net']['files'] = 'Files';
$tab['ipv4net']['history'] = 'History';
$tabrev['ipv4net'] = array('default');
$tabhandler['ipv4net']['default'] = 'renderIPv4Network';
$tabhandler['ipv4net']['properties'] = 'renderIPv4NetworkProperties';
$tabhandler['ipv4net']['liveptr'] = 'renderLivePTR';
$tabhandler['ipv4net']['tags'] = 'renderEntityTags';
$tabhandler['ipv4net']['files'] = 'renderFilesForEntity';
$tabhandler['ipv4net']['history'] = 'renderHistoryForAnything';
$trigger['ipv4net']['tags'] = 'trigger_tags';
$ophandler['ipv4net']['properties']['editRange'] = 'updIPv4Prefix';
$ophandler['ipv4net']['liveptr']['importPTRData'] = 'importPTRData';
$ophandler['ipv4net']['tags']['saveTags'] = 'saveEntityTags';
$ophandler['ipv4net']['files']['addFile'] = 'addFileToEntity';
$ophandler['ipv4net']['files']['linkFile'] = 'linkFileToEntity';
$ophandler['ipv4net']['files']['unlinkFile'] = 'unlinkFile';

$page['ipaddress']['parent'] = 'ipv4net';
$page['ipaddress']['bypass'] = 'ip';
$page['ipaddress']['bypass_type'] = 'inet4';
$tab['ipaddress']['default'] = 'Browse';
$tab['ipaddress']['properties'] = 'Properties';
$tab['ipaddress']['assignment'] = 'Allocation';
$tab['ipaddress']['editrslist'] = '[SLB real servers]';
$tab['ipaddress']['history'] = 'History';
$tabrev['ipaddress'] = array('default');
$tabhandler['ipaddress']['default'] = 'renderIPv4Address';
$tabhandler['ipaddress']['properties'] = 'renderIPv4AddressProperties';
$tabhandler['ipaddress']['assignment'] = 'renderIPv4AddressAllocations';
$tabhandler['ipaddress']['editrslist'] = 'dragon';
$tabhandler['ipaddress']['history'] = 'renderHistoryForAnything';
$ophandler['ipaddress']['properties']['editAddress'] = 'editAddress';
$ophandler['ipaddress']['assignment']['delIPv4Allocation'] = 'delIPv4Allocation';
$ophandler['ipaddress']['assignment']['updIPv4Allocation'] = 'updIPv4Allocation';
$ophandler['ipaddress']['assignment']['addIPv4Allocation'] = 'addIPv4Allocation';

$page['ipv4slb']['title'] = 'IPv4 SLB';
$page['ipv4slb']['parent'] = 'index';
$page['ipv4slb']['handler'] = 'renderIPv4SLB';

$page['ipv4vslist']['title'] = 'Virtual services';
$page['ipv4vslist']['parent'] = 'ipv4slb';
$tab['ipv4vslist']['default'] = 'View';
$tab['ipv4vslist']['edit'] = 'Edit';
$tabrev['ipv4vslist'] = array('default');
$tabhandler['ipv4vslist']['default'] = 'renderVSList';
$tabhandler['ipv4vslist']['edit'] = 'renderVSListEditForm';
$ophandler['ipv4vslist']['edit']['add'] = 'addVService';
$ophandler['ipv4vslist']['edit']['del'] = 'deleteVService';
$ophandler['ipv4vslist']['edit']['upd'] = 'updateVService';

$page['ipv4vs']['parent'] = 'ipv4vslist';
$page['ipv4vs']['bypass'] = 'vs_id';
$page['ipv4vs']['bypass_type'] = 'uint';
$tab['ipv4vs']['default'] = 'View';
$tab['ipv4vs']['edit'] = 'Edit';
$tab['ipv4vs']['editlblist'] = 'Load balancers';
$tab['ipv4vs']['tags'] = 'Tags';
$tab['ipv4vs']['files'] = 'Files';
$tabrev['ipv4vs'] = array('default');
$tabhandler['ipv4vs']['default'] = 'renderVirtualService';
$tabhandler['ipv4vs']['edit'] = 'renderEditVService';
$tabhandler['ipv4vs']['editlblist'] = 'renderVServiceLBForm';
$tabhandler['ipv4vs']['tags'] = 'renderEntityTags';
$tabhandler['ipv4vs']['files'] = 'renderFilesForEntity';
$trigger['ipv4vs']['tags'] = 'trigger_tags';
$ophandler['ipv4vs']['edit']['updIPv4VS'] = 'updateVService';
$ophandler['ipv4vs']['editlblist']['addLB'] = 'addLoadBalancer';
$ophandler['ipv4vs']['editlblist']['delLB'] = 'deleteLoadBalancer';
$ophandler['ipv4vs']['editlblist']['updLB'] = 'updateLoadBalancer';
$ophandler['ipv4vs']['tags']['saveTags'] = 'saveEntityTags';
$ophandler['ipv4vs']['files']['addFile'] = 'addFileToEntity';
$ophandler['ipv4vs']['files']['linkFile'] = 'linkFileToEntity';
$ophandler['ipv4vs']['files']['unlinkFile'] = 'unlinkFile';

$page['ipv4rsplist']['title'] = 'RS pools';
$page['ipv4rsplist']['parent'] = 'ipv4slb';
$tab['ipv4rsplist']['default'] = 'View';
$tab['ipv4rsplist']['edit'] = 'Edit';
$tabrev['ipv4rsplist'] = array('default');
$tabhandler['ipv4rsplist']['default'] = 'renderRSPoolList';
$tabhandler['ipv4rsplist']['edit'] = 'editRSPools';
$ophandler['ipv4rsplist']['edit']['add'] = 'addRSPool';
$ophandler['ipv4rsplist']['edit']['del'] = 'deleteRSPool';
$ophandler['ipv4rsplist']['edit']['upd'] = 'updateRSPool';

$page['ipv4rspool']['parent'] = 'ipv4rsplist';
$page['ipv4rspool']['bypass'] = 'pool_id';
$page['ipv4rspool']['bypass_type'] = 'uint';
$tab['ipv4rspool']['default'] = 'View';
$tab['ipv4rspool']['edit'] = 'Edit';
$tab['ipv4rspool']['editlblist'] = 'Load balancers';
$tab['ipv4rspool']['editrslist'] = 'RS list';
$tab['ipv4rspool']['rsinservice'] = 'RS in service';
$tab['ipv4rspool']['tags'] = 'Tags';
$tab['ipv4rspool']['files'] = 'Files';
$tabrev['ipv4rspool'] = array('default');
$trigger['ipv4rspool']['rsinservice'] = 'trigger_poolrscount';
$trigger['ipv4rspool']['tags'] = 'trigger_tags';
$tabhandler['ipv4rspool']['default'] = 'renderRSPool';
$tabhandler['ipv4rspool']['edit'] = 'renderEditRSPool';
$tabhandler['ipv4rspool']['editrslist'] = 'renderRSPoolServerForm';
$tabhandler['ipv4rspool']['editlblist'] = 'renderRSPoolLBForm';
$tabhandler['ipv4rspool']['rsinservice'] = 'renderRSPoolRSInServiceForm';
$tabhandler['ipv4rspool']['tags'] = 'renderEntityTags';
$tabhandler['ipv4rspool']['files'] = 'renderFilesForEntity';
$ophandler['ipv4rspool']['edit']['updIPv4RSP'] = 'updateRSPool';
$ophandler['ipv4rspool']['editrslist']['addRS'] = 'addRealServer';
$ophandler['ipv4rspool']['editrslist']['delRS'] = 'deleteRealServer';
$ophandler['ipv4rspool']['editrslist']['updRS'] = 'updateRealServer';
$ophandler['ipv4rspool']['editrslist']['addMany'] = 'addRealServers';
$ophandler['ipv4rspool']['editlblist']['addLB'] = 'addLoadBalancer';
$ophandler['ipv4rspool']['editlblist']['delLB'] = 'deleteLoadBalancer';
$ophandler['ipv4rspool']['editlblist']['updLB'] = 'updateLoadBalancer';
$ophandler['ipv4rspool']['rsinservice']['upd'] = 'updateRSInService';
$ophandler['ipv4rspool']['tags']['saveTags'] = 'saveEntityTags';
$ophandler['ipv4rspool']['files']['addFile'] = 'addFileToEntity';
$ophandler['ipv4rspool']['files']['linkFile'] = 'linkFileToEntity';
$ophandler['ipv4rspool']['files']['unlinkFile'] = 'unlinkFile';
$ajaxhandler['ipv4rspool']['editrslist']['getObjectsInet4List'] = 'getObjectsInet4List';

$page['rservers']['title'] = 'Real servers';
$page['rservers']['parent'] = 'ipv4slb';
$page['rservers']['handler'] = 'renderRealServerList';

$page['lbs']['title'] = 'Load balancers';
$page['lbs']['parent'] = 'ipv4slb';
$page['lbs']['handler'] = 'renderLBList';

$page['search']['handler'] = 'renderSearchResults';
$page['search']['parent'] = 'index';
$page['search']['bypass'] = 'q';

$page['config']['title'] = 'Configuration';
$page['config']['handler'] = 'renderConfigMainpage';
$page['config']['parent'] = 'index';

$page['userlist']['title'] = 'Local users';
$page['userlist']['parent'] = 'config';
$tab['userlist']['default'] = 'View';
$tab['userlist']['edit'] = 'Edit';
$tabrev['userlist'] = array('default', 'edit');
$tabhandler['userlist']['default'] = 'renderUserList';
$tabhandler['userlist']['edit'] = 'renderUserListEditor';
$ophandler['userlist']['edit']['updateUser'] = 'updateUser';
$ophandler['userlist']['edit']['createUser'] = 'createUser';

$page['user']['parent'] = 'userlist';
$page['user']['bypass'] = 'user_id';
$page['user']['bypass_type'] = 'uint';
$tab['user']['default'] = 'View';
$tab['user']['tags'] = 'Tags';
$tab['user']['files'] = 'Files';
$tabrev['user'] = array('default', 'tags', 'files');
$tabhandler['user']['default'] = 'renderUser';
$tabhandler['user']['tags'] = 'renderEntityTags';
$tabhandler['user']['files'] = 'renderFilesForEntity';
$ophandler['user']['tags']['saveTags'] = 'saveEntityTags';
$ophandler['user']['files']['addFile'] = 'addFileToEntity';
$ophandler['user']['files']['linkFile'] = 'linkFileToEntity';
$ophandler['user']['files']['unlinkFile'] = 'unlinkFile';

$page['perms']['title'] = 'Permissions';
$page['perms']['parent'] = 'config';
$tab['perms']['default'] = 'View';
$tab['perms']['edit'] = 'Edit';
$tabrev['perms'] = array('default', 'edit');
$tabhandler['perms']['default'] = 'renderRackCodeViewer';
$tabhandler['perms']['edit'] = 'renderRackCodeEditor';
$ophandler['perms']['edit']['saveRackCode'] = 'saveRackCode';
$ajaxhandler['perms']['edit']['verifyCode'] = 'verifyRackCode';

$page['portmap']['title'] = 'Port compatibility map';
$page['portmap']['parent'] = 'config';
$tab['portmap']['default'] = 'View';
$tab['portmap']['edit'] = 'Change';
$tabrev['portmap'] = array('default', 'edit');
$tabhandler['portmap']['default'] = 'renderPortMapViewer';
$tabhandler['portmap']['edit'] = 'renderPortMapEditor';
$ophandler['portmap']['edit']['save'] = 'savePortMap';

$page['attrs']['title'] = 'Attributes';
$page['attrs']['parent'] = 'config';
$tab['attrs']['default'] = 'View';
$tab['attrs']['editattrs'] = 'Edit attributes';
$tab['attrs']['editmap'] = 'Edit map';
$tabrev['attrs'] = array('default');
$tabhandler['attrs']['default'] = 'renderAttributes';
$tabhandler['attrs']['editattrs'] = 'renderEditAttributesForm';
$tabhandler['attrs']['editmap'] = 'renderEditAttrMapForm';
$ophandler['attrs']['editattrs']['add'] = 'createAttribute';
$ophandler['attrs']['editattrs']['upd'] = 'changeAttribute';
$ophandler['attrs']['editattrs']['del'] = 'deleteAttribute';
$ophandler['attrs']['editmap']['add'] = 'supplementAttrMap';
$ophandler['attrs']['editmap']['del'] = 'reduceAttrMap';

$page['dict']['title'] = 'Dictionary';
$page['dict']['parent'] = 'config';
$tab['dict']['default'] = 'View';
$tab['dict']['chapters'] = 'Manage chapters';
$tabrev['dict'] = array('default');
$tabhandler['dict']['default'] = 'renderDictionary';
$tabhandler['dict']['chapters'] = 'renderChaptersEditor';
$ophandler['dict']['chapters']['del'] = 'delChapter';
$ophandler['dict']['chapters']['upd'] = 'updateChapter';
$ophandler['dict']['chapters']['add'] = 'addChapter';

$page['chapter']['parent'] = 'dict';
$page['chapter']['bypass'] = 'chapter_no';
$page['chapter']['bypass_type'] = 'uint';
$tab['chapter']['default'] = 'View';
$tab['chapter']['edit'] = 'Edit';
$tabrev['chapter'] = array('default');
$tabhandler['chapter']['default'] = 'renderChapter';
$tabhandler['chapter']['edit'] = 'renderChapterEditor';
$ophandler['chapter']['edit']['del'] = 'reduceDictionary';
$ophandler['chapter']['edit']['upd'] = 'updateDictionary';
$ophandler['chapter']['edit']['add'] = 'supplementDictionary';

$page['ui']['title'] = 'User interface';
$page['ui']['parent'] = 'config';
$tab['ui']['default'] = 'View';
$tab['ui']['edit'] = 'Change';
$tab['ui']['reset'] = 'Reset';
$tabrev['ui'] = array('default', 'edit', 'reset');
$tabhandler['ui']['default'] = 'renderUIConfig';
$tabhandler['ui']['edit'] = 'renderUIConfigEditForm';
$tabhandler['ui']['reset'] = 'renderUIResetForm';
$ophandler['ui']['edit']['upd'] = 'updateUI';
$ophandler['ui']['reset']['go'] = 'resetUIConfig';

$page['tagtree']['title'] = 'Tag tree';
$page['tagtree']['parent'] = 'config';
$tab['tagtree']['default'] = 'View';
$tab['tagtree']['edit'] = 'Edit';
$tabrev['tagtree'] = array('default');
$tabhandler['tagtree']['default'] = 'renderTagTree';
$tabhandler['tagtree']['edit'] = 'renderTagTreeEditor';
$ophandler['tagtree']['edit']['destroyTag'] = 'destroyTag';
$ophandler['tagtree']['edit']['createTag'] = 'createTag';
$ophandler['tagtree']['edit']['updateTag'] = 'updateTag';

$page['myaccount']['title'] = 'My account';
$page['myaccount']['parent'] = 'config';
$tab['myaccount']['default'] = 'Info';
$tab['myaccount']['mypassword'] = 'Password change';
$tab['myaccount']['myrealname'] = '[Real name change]';
$tabrev['myaccount'] = array('default', 'mypassword', 'myrealname');
$trigger['myaccount']['mypassword'] = 'trigger_passwdchange';
$tabhandler['myaccount']['default'] = 'renderMyAccount';
$tabhandler['myaccount']['mypassword'] = 'renderMyPasswordEditor';
$tabhandler['myaccount']['myrealname'] = 'dragon';
$ophandler['myaccount']['mypassword']['changeMyPassword'] = 'changeMyPassword';

$page['reports']['title'] = 'Reports';
$page['reports']['parent'] = 'index';
$tab['reports']['default'] = 'System';
$tab['reports']['rackcode'] = 'RackCode';
$tab['reports']['ipv4'] = 'IPv4';
$tab['reports']['local'] = getConfigVar ('enterprise');
$tabrev['reports'] = array('default', 'ipv4', 'local');
$trigger['reports']['local'] = 'trigger_localreports';
$tabhandler['reports']['default'] = 'renderSystemReports';
$tabhandler['reports']['rackcode'] = 'renderRackCodeReports';
$tabhandler['reports']['ipv4'] = 'renderIPv4Reports';
$tabhandler['reports']['local'] = 'renderLocalReports';

$page['history']['title'] = 'History';
$page['history']['parent'] = 'index';
$tab['history']['default'] = 'View';
$tab['history']['milestones'] = 'Milestones';
$tabrev['history'] = array('default', 'milestones');
$tabhandler['history']['default'] = 'renderMainHistory';
$tabhandler['history']['milestones'] = 'renderMilestonesHistory';
$ophandler['history']['milestones']['add_new_milestone'] = 'addNewMilestone';
$msgcode['addNewMilestone']['OK'] = 80;

$page['files']['title'] = 'Files';
$page['files']['parent'] = 'index';
$tabrev['files'] = array('default');
$tab['files']['default'] = 'Browse';
$tab['files']['manage'] = 'Manage';
$tabhandler['files']['default'] = 'renderFileBrowser';
$tabhandler['files']['manage'] = 'renderFileManager';
$ophandler['files']['manage']['addFile'] = 'addFileWithoutLink';
$ophandler['files']['manage']['unlinkFile'] = 'unlinkFile';
$ophandler['files']['manage']['deleteFile'] = 'deleteFile';

$page['file']['bypass'] = 'file_id';
$page['file']['bypass_type'] = 'uint';
$page['file']['parent'] = 'files';
$tab['file']['default'] = 'View';
$tab['file']['edit'] = 'Properties';
$tab['file']['tags'] = 'Tags';
$tab['file']['editText'] = 'Edit text';
$tab['file']['history'] = 'History';
$tabrev['file'] = array('default');
$tab['file']['replaceData'] = 'Upload replacement';
$trigger['file']['tags'] = 'trigger_tags';
$trigger['file']['editText'] = 'trigger_file_editText';
$tabhandler['file']['default'] = 'renderFile';
$tabhandler['file']['edit'] = 'renderFileProperties';
$tabhandler['file']['tags'] = 'renderEntityTags';
$tabhandler['file']['editText'] = 'renderTextEditor';
$tabhandler['file']['history'] = 'renderHistoryForAnything';
$ophandler['file']['default']['replaceFile'] = 'replaceFile';
$tabhandler['file']['replaceData'] = 'renderFileReuploader';
$ophandler['file']['edit']['updateFile'] = 'updateFile';
$ophandler['file']['tags']['saveTags'] = 'saveEntityTags';
$ophandler['file']['editText']['updateFileText'] = 'updateFileText';
$ophandler['file']['replaceData']['replaceFile'] = 'replaceFile';

function getPageForObject($table, $id, $rev)
{
	$ret = array();
	switch ($table)
	{
		case 'Rack':
			$ret['page'] = 'rack';
			$ret['rack_id'] = $id;
			break;
		case 'RackSpace':
			$rack = Database::get('rack_id', 'RackSpace', $id, $rev);
			$ret['page'] = 'rack';
			$ret['rack_id'] = $rack;
			break;
		case 'RackRow':
			$ret['page'] = 'row';
			$ret['row_id'] = $id;
			break;
		case 'RackObject':
			$ret['page'] = 'object';
			$ret['object_id'] = $id;
			break;
		case 'Port':
			$object = Database::get('object_id', 'Port', $id, $rev);
			$ret['page'] = 'object';
			$ret['object_id'] = $object;
			break;
		case 'Link':
			$porta = Database::get('porta', 'Link', $id, $rev);
			$object = Database::get('object_id', 'Port', $porta, $rev);
			$ret['page'] = 'object';
			$ret['object_id'] = $object;
			break;
		case 'IPv4Address':
			$ip = Database::get('ip', 'IPv4Address', $id, $rev);
			$ret['page'] = 'ipaddress';
			$ret['ip'] = ip_long2quad($ip);
			break;
		case 'IPv4Allocation':
			$ip = Database::get('ip', 'IPv4Allocation', $id, $rev);
			$ret['page'] = 'ipaddress';
			$ret['ip'] = ip_long2quad($ip);
			break;
		case 'IPv4NAT':
			$object = Database::get('object_id', 'IPv4Allocation', $id, $rev);
			$ret['page'] = 'object';
			$ret['object_id'] = $object;
			break;
		case 'IPv4Network':
			$ret['page'] = 'ipv4net';
			$ret['id'] = $id;
			break;
		case 'Attribute':
			$ret['page'] = 'attrs';
			break;
		case 'AttributeMap':
			$ret['page'] = 'attrs';
			break;
		case 'AttributeValue':
			$object = Database::get('object_id', 'AttributeValue', $id, $rev);
			$ret['page'] = 'object';
			$ret['object_id'] = $object;
			break;
		case 'Dictionary':
			$ret['page'] = 'dict';
			break;
		case 'Chapter':
			$ret['page'] = 'chapter';
			$ret['chapter_no'] = $id;
			break;
		case 'IPv4LB':
			$object = Database::get('object_id', 'IPv4LB', $id, $rev);
			$ret['page'] = 'object';
			$ret['object_id'] = $object;
			break;
		case 'IPv4RS':
			$ip = Database::get('rsip', 'IPv4RS', $id, $rev);
			$ret['page'] = 'ipaddress';
			$ret['ip'] = $ip;
			break;
		case 'IPv4RSPool':
			$ret['page'] = 'ipv4rspool';
			$ret['pool_id'] = $id;
			break;
		case 'IPv4VS':
			$ret['page'] = 'ipv4vs';
			$ret['vs_id'] = $id;
			break;
		case 'TagStorage':
			$realm = Database::get('entity_realm', 'TagStorage', $id, $rev);
			$id = Database::get('entity_id', 'TagStorage', $id, $rev);
			if ($realm == 'file')
				return getPageForObject('File', $id, $rev);
			elseif ($realm == 'ipv4net')
				return getPageForObject('IPv4Network', $id, $rev);
			elseif ($realm == 'ipv4vs')
				return getPageForObject('IPv4VS', $id, $rev);
			elseif ($realm == 'ipv4rspool')
				return getPageForObject('IPv4RSPool', $id, $rev);
			elseif ($realm == 'object')
				return getPageForObject('RackObject', $id, $rev);
			elseif ($realm == 'rack')
				return getPageForObject('Rack', $id, $rev);
			elseif ($realm == 'user')
			{
				$ret['page'] = 'user';
				$ret['user_id'] = $id;
			}
			else
				$ret['page'] = 'tagtree';
			break;
		case 'TagTree':
			$ret['page'] = 'tagtree';
			break;
		case 'FileLink':
			$id = Database::get('file_id', 'FileLink', $id, $rev);
			$ret['page'] = 'file';
			$ret['file_id'] = $id;
			break;
		case 'File':
			$ret['page'] = 'file';
			$ret['file_id'] = $id;
			break;
		default:
			throw new Exception("Unknown table type $table");
	}
	return $ret;
}


?>

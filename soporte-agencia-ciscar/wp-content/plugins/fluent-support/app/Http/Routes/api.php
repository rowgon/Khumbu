<?php

defined('ABSPATH') or die;

/**
 * @var $router FluentSupport\Framework\Http\Router
 */

$router->prefix('mailboxes')->withPolicy('AdminSettingsPolicy')->group(function ($router) {
    $router->get('/', 'MailBoxController@index');
    $router->post('/', 'MailBoxController@save');
    $router->get('/{id}', 'MailBoxController@get')->int('id');
    $router->put('/{id}', 'MailBoxController@update')->int('id');
    $router->delete('/{id}', 'MailBoxController@delete')->int('id');
    $router->put('/{id}/move_tickets', 'MailBoxController@moveTickets')->int('id');

    $router->get('/{id}/tickets', 'MailBoxController@getTickets')->int('id');

    $router->get('/{id}/email_settings', 'MailBoxController@getEmailSettings')->int('id');
    $router->get('/{id}/email_configs', 'MailBoxController@getEmailsSetups');
    $router->put('/{id}/email_settings', 'MailBoxController@saveEmailSettings')->int('id');
    $router->put('/{id}/set_default', 'MailBoxController@setAsDefault')->int('id');
});

$router->prefix('tickets')->withPolicy('AgentTicketPolicy')->group(function ($router) {

    $router->get('my_stats', 'AgentController@myStats');
    $router->get('agent_performance', 'AgentController@agentPerformance');
    $router->get('/', 'TicketController@index');
    $router->post('/', 'TicketController@createTicket');
    $router->get('/label-search', 'TicketController@fetchLabelSearch');
    $router->post('/label-search', 'TicketController@storeOrUpdateLabelSearch');
    $router->delete('/{label_search_id}/label-search', 'TicketController@deleteLabelSearch')->int('label_search_id');

    $router->get('/{ticket_id}/mentionable-agents', 'TicketController@getMentionableAgents')->int('ticket_id');
    $router->get('/{ticket_id}', 'TicketController@getTicket')->int('ticket_id');

    $router->get('/{ticket_id}/widgets', 'TicketController@getTicketWidgets')->int('ticket_id');
    $router->post('/{ticket_id}/responses', 'TicketController@createResponse')->int('ticket_id');

    $router->post('/{ticket_id}/draft', 'TicketController@createOrUpdatDraft')->int('ticket_id');
    $router->get('/{ticket_id}/draft', 'TicketController@getDraft')->int('ticket_id');
    $router->delete('/{draft_id}/draft', 'TicketController@deleteDraft')->int('draft_id');

    $router->get('/{ticket_id}/live_activity', 'TicketController@getLiveActivity')->int('ticket_id');
    $router->delete('/{ticket_id}/live_activity', 'TicketController@removeLiveActivity')->int('ticket_id');

    $router->put('/{ticket_id}/responses/{response_id}', 'TicketController@updateResponse')
        ->int('ticket_id')
        ->int('response_id');

    $router->put('/{ticket_id}/approve_draft_response/{response_id}', 'TicketController@approveDraftResponse')
        ->int('ticket_id')
        ->int('response_id');

    $router->delete('/{ticket_id}/responses/{response_id}', 'TicketController@deleteResponse')
        ->int('ticket_id')
        ->int('response_id');

    $router->put('/{ticket_id}/property', 'TicketController@updateTicketProperty')->int('ticket_id');

    $router->post('/{ticket_id}/tags', 'TicketController@addTag')->int('ticket_id');
    $router->delete('/{ticket_id}/tags/{tag_id}', 'TicketController@detachTag')->int('ticket_id')->int('tag_id');

    $router->post('/{ticket_id}/close', 'TicketController@closeTicket')->int('ticket_id');
    $router->delete('/{ticket_id}/delete', 'TicketController@deleteTicket')->int('ticket_id');
    $router->post('/{ticket_id}/re-open', 'TicketController@reOpenTicket')->int('ticket_id');
    $router->put('/{ticket_id}/change-customer', 'TicketController@changeTicketCustomer')->int('ticket_id');
    $router->get('/{ticket_id}/custom-data', 'TicketController@getTicketCustomData')->int('ticket_id');

    $router->get('fluent-booking/event-types', 'TicketController@getFluentBookingEventTypes');
    $router->get('/{ticket_id}/fluent-booking/availability', 'TicketController@getFluentBookingAvailability')->int('ticket_id');
    $router->post('/{ticket_id}/fluent-booking/booking-link', 'TicketController@createFluentBookingLink')->int('ticket_id');
    $router->get('/{ticket_id}/fluent-booking/meetings', 'TicketController@getFluentBookingMeetings')->int('ticket_id');

    $router->post('bulk-actions', 'TicketController@doBulkActions'); // close_tickets | delete_tickets | assign_agent | assign_tags
    $router->post('bulk-reply', 'TicketController@doBulkReplies');

    $router->post('sync-fluentcrm-tags', 'TicketController@syncFluentCrmTags');
    $router->post('sync-fluentcrm-lists', 'TicketController@syncFluentCrmLists');

    $router->get('fluent-boards/boards', 'FluentBoardsController@getBoards');
    $router->get('fluent-boards/stages/{board_id}', 'FluentBoardsController@getStages')->int('board_id');
    $router->post('fluent-boards/stages', 'FluentBoardsController@createTask');

    $router->get('search-contact', 'CustomerController@searchContact');

    $router->get('ping', 'AgentController@ping');
    $router->get('ticket-essentials', 'TicketController@getTicketEssentials');
    $router->get('agent-insights', 'AgentController@getAgentInsights');
});

$router->get('widgets', 'WidgetsController')->withPolicy('AgentTicketPolicy');

$router->prefix('notifications')->withPolicy('AgentTicketPolicy')->group(function ($router) {
    $router->get('/', 'NotificationController@index');
    $router->get('/unread', 'NotificationController@unread');
    $router->get('/unread-count', 'NotificationController@unreadCount');
    $router->post('/mark-all-read', 'NotificationController@markAllRead');
    $router->post('/{notification_id}/mark-read', 'NotificationController@markRead')->int('notification_id');
});

$router->prefix('products')->withPolicy('AdminSettingsPolicy')->group(function ($router) {
    $router->get('/', 'ProductController@index');
    $router->post('/', 'ProductController@create');
    $router->get('/{product_id}', 'ProductController@get')->int('product_id');
    $router->post('/{product_id}', 'ProductController@create')->int('product_id');
    $router->put('/{product_id}', 'ProductController@update')->int('product_id');
    $router->delete('/{product_id}', 'ProductController@delete')->int('product_id');
});

$router->get('me', 'TicketController@me')->withPolicy('PortalPolicy');

$router->get('options/countries', 'OptionsController@getCountries')->withPolicy('PortalPolicy');

$router->post('ticket_file_upload', 'UploaderController@uploadTicketFiles')
    ->withPolicy('PortalPolicy');

$router->prefix('settings')->withPolicy('AdminSettingsPolicy')->group(function ($router) {
    $router->get('/', 'SettingsController@getSettings');
    $router->post('/', 'SettingsController@saveSettings');
    $router->get('/integration-settings', 'SettingsController@getIntegrationSettings');
    $router->get('/integration', 'IntegrationController@getSettings');
    $router->post('/integration', 'IntegrationController@saveSettings');
    $router->get('/slack-integration', 'SlackController@getSettings');
    $router->post('/slack-integration', 'SlackController@saveSettings');
    $router->get('/pages', 'SettingsController@getPages');
    $router->post('/setup', 'SettingsController@setupPortal');
    $router->post('/setup-installation', 'SettingsController@setupInstallation');
    $router->post('/recaptcha-settings', 'SettingsController@saveReCaptchaSettings');
    $router->get('/recaptcha-settings', 'SettingsController@getReCaptchaSettings');
    $router->get('/integration-statuses', 'SettingsController@integrationStatuses');

    $router->get('/fluentcrm-settings', 'SettingsController@getFluentCRMSettings');
    $router->post('/install-fluentcrm', 'SettingsController@installFluentCRM');

    // Upload Settings
    $router->get('/remote-upload-settings', 'SettingsController@getRemoteUploadSettings');
    $router->post('/update-remote-upload-driver', 'SettingsController@updateRemoteUploadDriver');

    $router->get('/ai-integration', 'SettingsController@getAIProviderSettings');
    $router->post('/ai-integration', 'SettingsController@saveAIProviderSettings');
    $router->post('/ai-integration/disconnect', 'SettingsController@disconnectAIProvider');
    $router->get('/settings-menu', 'SettingsController@getSettingsMenu');
    $router->get('/fluent-bot-integration', 'SettingsController@getFluentBotSettings');
    $router->post('/fluent-bot-integration', 'SettingsController@saveFluentBotSettings');
    $router->get('/fluent-bot-presets', 'SettingsController@getFluentBotPresets');
    $router->post('/fluent-bot-presets', 'SettingsController@saveFluentBotPresets');

    $router->get('/mcp', 'McpSettingsController@getStatus');
    $router->post('/mcp/toggle', 'McpSettingsController@toggle');
    $router->post('/mcp/install-adapter', 'McpSettingsController@installAdapter');
    $router->get('/mcp/config-snippets', 'McpSettingsController@getConfigSnippets');
});

$router->prefix('agents')->withPolicy('AgentPolicy')->group(function ($router) {
    $router->get('/', 'AgentController@index');
    $router->post('/', 'AgentController@addAgent');
    $router->put('/{agent_id}', 'AgentController@updateAgent')->int('agent_id');
    $router->delete('/{agent_id}', 'AgentController@deleteAgent')->int('agent_id');
    $router->post('/avatar/{agent_id}', 'AgentController@addOrUpdateProfileImage')->int('agent_id');
    $router->post('/reset_avatar/{agent}', 'AgentController@resetAvatar')->int('agent');
});

$router->prefix('agent-groups')->withPolicy('AdminSensitivePolicy')->group(function ($router) {
    $router->get('/', 'AgentGroupController@index');
    $router->post('/', 'AgentGroupController@create');
    $router->get('/{group_id}', 'AgentGroupController@get')->int('group_id');
    $router->put('/{group_id}', 'AgentGroupController@update')->int('group_id');
    $router->delete('/{group_id}', 'AgentGroupController@delete')->int('group_id');
});

$router->prefix('reports')->withPolicy('ReportPolicy')->group(function ($router) {
    $router->get('/', 'ReportingController@getOverallReports');
    $router->get('/tickets-growth', 'ReportingController@getTicketsChart');
    $router->get('/tickets-resolve-growth', 'ReportingController@getResolveChart');
    $router->get('/response-growth', 'ReportingController@getResponseChart');
    $router->get('/agents-summary', 'ReportingController@getAgentsSummary');
    $router->get('/day-time-stats', 'ReportingController@dayTimeStats');
    $router->get('/ticket-response-stats', 'ReportingController@ticketResponseStats');
    $router->get('/stats', 'ReportingController@getStats');
});

$router->prefix('my-reports')->withPolicy('AgentTicketPolicy')->group(function ($router) {
    $router->get('/', 'ReportingController@getAgentOverallReports');
    $router->get('/tickets-resolve-growth', 'ReportingController@getAgentResolveChart');
    $router->get('/response-growth', 'ReportingController@getAgentResponseChart');
    $router->get('/my-summary', 'ReportingController@getPersonalSummary');
});

$router->prefix('product-reports')->withPolicy('AdminSensitivePolicy')->group(function ($router) {
    $router->get('/tickets-growth', 'ReportingController@getTicketsChart');
    $router->get('/tickets-resolve-growth', 'ReportingController@getResolveChart');
    $router->get('/response-growth', 'ReportingController@getResponseGrowthChart');
    $router->get('/product-reports-summary', 'ReportingController@getProductsSummary');
});

$router->prefix('mailbox-reports')->withPolicy('AdminSensitivePolicy')->group(function ($router) {
    $router->get('/tickets-growth', 'ReportingController@getTicketsChart');
    $router->get('/tickets-resolve-growth', 'ReportingController@getResolveChart');
    $router->get('/response-growth', 'ReportingController@getResponseGrowthChart');
    $router->get('/mailbox-reports-summary', 'ReportingController@getMailBoxesSummary');
});

$router->prefix('agent-group-reports')->withPolicy('AdminSensitivePolicy')->group(function ($router) {
    $router->get('/tickets-growth', 'ReportingController@getTicketsChart');
    $router->get('/tickets-resolve-growth', 'ReportingController@getResolveChart');
    $router->get('/response-growth', 'ReportingController@getResponseChart');
    $router->get('/agent-groups-summary', 'ReportingController@getAgentGroupsSummary');
});

$router->prefix('customers')->withPolicy('AdminSensitivePolicy')->group(function ($router) {
    $router->get('/', 'CustomerController@index');
    $router->post('/', 'CustomerController@create');
    $router->get('/customerField/{customer_id}', 'CustomerController@customerField')->int('customer_id');

    $router->get('/{customer_id}', 'CustomerController@getCustomer')->int('customer_id');
    $router->put('/{customer_id}', 'CustomerController@update')->int('customer_id');

    //We will remove this method.
    $router->delete('/{customer_id}', 'CustomerController@delete')->int('customer_id');
    $router->delete('/bulk-delete', 'CustomerController@bulkDelete');

    $router->post('/profile_image/{customer_id}', 'CustomerController@addOrUpdateProfileImage')->int('customer_id');
    $router->post('/reset_avatar/{customer}', 'CustomerController@resetAvatar')->int('customer');
});

$router->prefix('customer-portal')->withPolicy('PortalPolicy')->group(function ($router) {

    $router->get('public_options', 'CustomerPortalController@getPublicOptions');
    $router->get('custom-fields-rendered', 'CustomerPortalController@getCustomFieldsRender');

    $router->get('tickets', 'CustomerPortalController@getTickets');
    $router->post('tickets', 'CustomerPortalController@createTicket');

    $router->get('tickets/{ticket_id}', 'CustomerPortalController@getTicket')->int('ticket_id');
    $router->post('tickets/{ticket_id}/responses', 'CustomerPortalController@createResponse')->int('ticket_id');

    $router->post('/tickets/{ticket_id}/close', 'CustomerPortalController@closeTicket')->int('ticket_id');
    $router->post('/tickets/{ticket_id}/re-open', 'CustomerPortalController@reOpenTicket')->int('ticket_id');

    $router->post('ticket_file_upload', 'UploaderController@uploadTicketFiles');

    $router->get('me', 'TicketController@me');

    $router->post('/tickets/{ticket_id}/agent-feedback', 'CustomerPortalController@agentFeedbackRating')->int('ticket_id');

    $router->post('logout', 'CustomerPortalController@logout');
});

$router->prefix('public')->withPolicy('PublicPolicy')->group(function ($router) {
    $router->post('telegram_bot_response/{token}', 'ChatMessageParserController@handleTelegramWebhook')->alphaNumDash('token');
    $router->post('slack_response/{token}', 'ChatMessageParserController@handleSlackEvent')->alphaNumDash('token');
    $router->get('/authorize', 'AuthorizeController@handleHelpScoutAuthorization');
});

$router->prefix('fluent-bot')->withPolicy('AgentTicketPolicy')->group(function ($router) {
    $router->get('/preset-prompts', 'FluentBotController@getPresetPrompts');
    $router->get('/runtime-config', 'FluentBotController@getRuntimeConfig');
    $router->post('/{id}/feedback', 'FluentBotController@createFeedback')->int('id');
    $router->delete('/{id}/feedback/{feedback_id}', 'FluentBotController@deleteFeedback')->int('id')->int('feedback_id');
    $router->post('/{id}/generate-response', 'FluentBotController@generateResponse')->int('id');
    $router->post('/{id}/generate-stream-response', 'FluentBotController@generateStreamResponse')->int('id');
    $router->post('/{id}/get-ticket-summary', 'FluentBotController@getTicketSummary')->int('id');
    $router->post('/{id}/get-ticket-tone', 'FluentBotController@getTicketTone')->int('id');
    $router->get('/{id}/chat-id', 'FluentBotController@getChatId')->int('id');
    $router->post('/{id}/chat-id', 'FluentBotController@saveChatId')->int('id');
    $router->delete('/{id}/chat-id', 'FluentBotController@deleteChatId')->int('id');
    $router->get('/{id}/chat-messages', 'FluentBotController@getChatMessages')->int('id');
    $router->get('/{id}/conversations', 'FluentBotController@getConversations')->int('id');
    $router->post('/{id}/conversations/switch', 'FluentBotController@switchConversation')->int('id');
    $router->get('/{id}/context-selection', 'FluentBotController@getContextSelection')->int('id');
    $router->post('/{id}/context-selection', 'FluentBotController@saveContextSelection')->int('id');
});

$router->prefix('activity-logger')->withPolicy('ActivityLoggerPolicy')->group(function ($router) {
    $router->get('/', 'ActivityLoggerController@getActivities');
    $router->get('/settings', 'ActivityLoggerController@getSettings');
    $router->post('/settings', 'ActivityLoggerController@updateSettings');
});

$router->prefix('ai-activity-logger')->withPolicy('ActivityLoggerPolicy')->group(function ($router) {
    $router->get('/', 'AIActivityLoggerController@getAIActivities');
    $router->post('/settings', 'AIActivityLoggerController@updateSettings');
    $router->get('/settings', 'AIActivityLoggerController@getSettings');
});

$router->post('signup', 'AuthController@signup')->withPolicy('PublicPolicy');

$router->post('login', 'AuthController@handleLogin')->withPolicy('PublicPolicy');

$router->prefix('two_fa')->withPolicy('PublicPolicy')->group(function ($router) {
    $router->post('/', 'TwofaController@verify2fa');
});


$router->post('reset_pass', 'AuthController@resetPassword')->withPolicy('PublicPolicy');

$router->prefix('ticket_importer')->withPolicy('AdminSettingsPolicy')->group(function ($router) {
    $router->get('/', 'TicketImportController@getStats');
    $router->post('/import', 'TicketImportController@importTickets');
    $router->delete('/delete', 'TicketImportController@deleteTickets');
});

$router->post('ticket_image_upload', 'UploaderController@uploadImage')
    ->withPolicy('AgentTicketPolicy');

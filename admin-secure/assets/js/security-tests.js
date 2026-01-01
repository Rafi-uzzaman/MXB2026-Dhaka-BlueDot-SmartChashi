/**
 * Security Center Testing Guide
 * Use browser console to test functionality
 */

// Test 1: Get Security Stats
console.log('Test 1: Getting security stats...');
fetch('../ajax/admin.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=get_security_stats'
})
.then(r => r.json())
.then(d => console.log('Stats:', d))
.catch(e => console.error('Error:', e));

// Test 2: Add IP Rule
console.log('Test 2: Adding IP rule...');
function testAddIPRule() {
    fetch('../ajax/admin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'add_ip_rule',
            ip_address: '192.168.1.100',
            rule_type: 'blacklist',
            reason: 'Test block'
        })
    })
    .then(r => r.json())
    .then(d => console.log('Add IP Result:', d))
    .catch(e => console.error('Error:', e));
}

// Test 3: Get IP Rules
console.log('Test 3: Getting IP rules...');
function testGetIPRules() {
    fetch('../ajax/admin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'get_ip_rules',
            page: 1,
            limit: 20
        })
    })
    .then(r => r.json())
    .then(d => console.log('IP Rules:', d))
    .catch(e => console.error('Error:', e));
}

// Test 4: Acknowledge Event
function testAcknowledgeEvent(eventId) {
    console.log('Test 4: Acknowledging event...');
    fetch('../ajax/admin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'acknowledge_security_event',
            event_id: eventId
        })
    })
    .then(r => r.json())
    .then(d => console.log('Acknowledge Result:', d))
    .catch(e => console.error('Error:', e));
}

// Test 5: UI Functions
console.log('Test 5: UI Functions available');
console.log('- showNotification("Test", "success")');
console.log('- showLoader("Testing...")');
console.log('- hideLoader()');
console.log('- showAddIPModal()');
console.log('- hideAddIPModal()');

// Run tests
console.log('\n=== Security Center Tests ===');
console.log('Run these functions in console:');
console.log('- testAddIPRule()');
console.log('- testGetIPRules()');
console.log('- testAcknowledgeEvent(1)');
console.log('- showNotification("Test notification", "success")');

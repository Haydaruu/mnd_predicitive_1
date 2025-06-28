#!/bin/bash

# Production Asterisk Setup Script for Predictive Dialer
echo "🚀 Setting up Asterisk for Production Predictive Dialer..."

# Backup existing configs
sudo cp /etc/asterisk/pjsip.conf /etc/asterisk/pjsip.conf.backup
sudo cp /etc/asterisk/extensions.conf /etc/asterisk/extensions.conf.backup
sudo cp /etc/asterisk/manager.conf /etc/asterisk/manager.conf.backup

# Configure PJSIP for production trunk
sudo tee /etc/asterisk/pjsip.conf > /dev/null <<EOF
[global]
type=global
user_agent=Asterisk-PredictiveDialer
allow_guest=no
debug=yes

[transport-udp]
type=transport
protocol=udp
bind=0.0.0.0:5060
external_media_address=YOUR_PUBLIC_IP
external_signaling_address=YOUR_PUBLIC_IP

; ======================
; SIP TRUNK CONFIGURATION
; ======================
[trunk-mnd]
type=registration
outbound_auth=trunk-mnd-auth
server_uri=sip:49.128.184.138
client_uri=sip:mnd@49.128.184.138
retry_interval=60
forbidden_retry_interval=600
expiration=3600
transport=transport-udp

[trunk-mnd-auth]
type=auth
auth_type=userpass
username=mnd
password=15\$2g238!2dh3vHF2d33d

[trunk-mnd-aor]
type=aor
contact=sip:49.128.184.138

[trunk-mnd-endpoint]
type=endpoint
transport=transport-udp
context=from-trunk
disallow=all
allow=ulaw
allow=alaw
allow=g729
outbound_auth=trunk-mnd-auth
aors=trunk-mnd-aor
from_user=mnd
from_domain=49.128.184.138
t38_udptl=no
rtp_symmetric=yes
rewrite_contact=yes
force_rport=yes
direct_media=no

[trunk-mnd-identify]
type=identify
endpoint=trunk-mnd-endpoint
match=49.128.184.138

; ======================
; AGENT ENDPOINTS (101-120)
; ======================
[agent-template](!)
type=endpoint
context=agents
disallow=all
allow=ulaw
allow=alaw
transport=transport-udp
direct_media=no
rtp_symmetric=yes
force_rport=yes

[agent-auth-template](!)
type=auth
auth_type=userpass

[agent-aor-template](!)
type=aor
max_contacts=1
qualify_frequency=30
remove_existing=yes

; Agent 101-120
[agent01](agent-template)
auth=agent01-auth
aors=agent01

[agent01-auth](agent-auth-template)
username=agent01
password=agent01pass

[agent01](agent-aor-template)

[agent02](agent-template)
auth=agent02-auth
aors=agent02

[agent02-auth](agent-auth-template)
username=agent02
password=agent02pass

[agent02](agent-aor-template)

[agent03](agent-template)
auth=agent03-auth
aors=agent03

[agent03-auth](agent-auth-template)
username=agent03
password=agent03pass

[agent03](agent-aor-template)

[agent04](agent-template)
auth=agent04-auth
aors=agent04

[agent04-auth](agent-auth-template)
username=agent04
password=agent04pass

[agent04](agent-aor-template)

[agent05](agent-template)
auth=agent05-auth
aors=agent05

[agent05-auth](agent-auth-template)
username=agent05
password=agent05pass

[agent05](agent-aor-template)

[agent06](agent-template)
auth=agent06-auth
aors=agent06

[agent06-auth](agent-auth-template)
username=agent06
password=agent06pass

[agent06](agent-aor-template)

[agent07](agent-template)
auth=agent07-auth
aors=agent07

[agent07-auth](agent-auth-template)
username=agent07
password=agent07pass

[agent07](agent-aor-template)

[agent08](agent-template)
auth=agent08-auth
aors=agent08

[agent08-auth](agent-auth-template)
username=agent08
password=agent08pass

[agent08](agent-aor-template)

[agent09](agent-template)
auth=agent09-auth
aors=agent09

[agent09-auth](agent-auth-template)
username=agent09
password=agent09pass

[agent09](agent-aor-template)

[agent10](agent-template)
auth=agent10-auth
aors=agent10

[agent10-auth](agent-auth-template)
username=agent10
password=agent10pass

[agent10](agent-aor-template)
EOF

# Configure Extensions for Predictive Dialer
sudo tee /etc/asterisk/extensions.conf > /dev/null <<EOF
[general]
static=yes
writeprotect=no
clearglobalvars=no

[globals]
TRUNK_PREFIX=99864
TRUNK_ENDPOINT=trunk-mnd-endpoint

[agents]
; Agent internal calls
exten => _agent0[1-9],1,Dial(PJSIP/\${EXTEN},20)
exten => _agent0[1-9],n,Voicemail(\${EXTEN}@default,u)
exten => _agent0[1-9],n,Hangup()

exten => _agent1[0-9],1,Dial(PJSIP/\${EXTEN},20)
exten => _agent1[0-9],n,Voicemail(\${EXTEN}@default,u)
exten => _agent1[0-9],n,Hangup()

[from-internal]
; Internal agent to agent calls
exten => _agent0[1-9],1,Dial(PJSIP/\${EXTEN},20)
exten => _agent0[1-9],n,Hangup()

exten => _agent1[0-9],1,Dial(PJSIP/\${EXTEN},20)
exten => _agent1[0-9],n,Hangup()

; Manual outbound calls (for testing)
exten => _X.,1,Set(CALLERID(num)=\${TRUNK_PREFIX})
exten => _X.,n,Dial(PJSIP/\${TRUNK_PREFIX}\${EXTEN}@\${TRUNK_ENDPOINT},60)
exten => _X.,n,Hangup()

[predictive-dialer]
; Main predictive dialer context
exten => s,1,NoOp(=== PREDICTIVE DIALER CALL START ===)
exten => s,n,Set(CALL_ID=\${CALL_ID})
exten => s,n,Set(CAMPAIGN_ID=\${CAMPAIGN_ID})
exten => s,n,Set(CUSTOMER_NAME=\${CUSTOMER_NAME})
exten => s,n,Set(CUSTOMER_PHONE=\${CUSTOMER_PHONE})
exten => s,n,Set(CALLERID(num)=\${TRUNK_PREFIX})
exten => s,n,NoOp(Calling customer: \${CUSTOMER_PHONE})

; Make outbound call with prefix
exten => s,n,Dial(PJSIP/\${TRUNK_PREFIX}\${CUSTOMER_PHONE}@\${TRUNK_ENDPOINT},30,gM(predictive-answered))
exten => s,n,Goto(s-\${DIALSTATUS},1)

; Handle different call statuses
exten => s-ANSWER,1,NoOp(Customer answered)
exten => s-ANSWER,n,Set(CDR(userfield)=ANSWERED)
exten => s-ANSWER,n,Queue(predictive-queue,t,,,300)
exten => s-ANSWER,n,Hangup()

exten => s-BUSY,1,NoOp(Customer busy)
exten => s-BUSY,n,Set(CDR(userfield)=BUSY)
exten => s-BUSY,n,Hangup()

exten => s-NOANSWER,1,NoOp(Customer no answer)
exten => s-NOANSWER,n,Set(CDR(userfield)=NOANSWER)
exten => s-NOANSWER,n,Hangup()

exten => s-CONGESTION,1,NoOp(Network congestion)
exten => s-CONGESTION,n,Set(CDR(userfield)=FAILED)
exten => s-CONGESTION,n,Hangup()

exten => s-CHANUNAVAIL,1,NoOp(Channel unavailable)
exten => s-CHANUNAVAIL,n,Set(CDR(userfield)=FAILED)
exten => s-CHANUNAVAIL,n,Hangup()

exten => _s-.,1,NoOp(Other status: \${EXTEN})
exten => _s-.,n,Set(CDR(userfield)=FAILED)
exten => _s-.,n,Hangup()

; Macro for answered calls
[macro-predictive-answered]
exten => s,1,NoOp(Customer answered, connecting to agent)
exten => s,n,Set(CDR(userfield)=ANSWERED)
exten => s,n,Return()

[predictive-queue-context]
; Context for queue operations
exten => s,1,NoOp(Entering predictive queue)
exten => s,n,Queue(predictive-queue,t,,,300)
exten => s,n,Hangup()

[from-trunk]
; Incoming calls from trunk
exten => _X.,1,NoOp(Incoming call from trunk: \${EXTEN})
exten => _X.,n,Dial(PJSIP/agent01,20)
exten => _X.,n,Voicemail(agent01@default,u)
exten => _X.,n,Hangup()

[default]
include => agents
include => from-internal
EOF

# Configure Manager for AMI
sudo tee /etc/asterisk/manager.conf > /dev/null <<EOF
[general]
enabled = yes
port = 5038
bindaddr = 0.0.0.0
displayconnects = yes
timestampevents = yes

[admin]
secret = amp111
deny = 0.0.0.0/0.0.0.0
permit = 127.0.0.1/255.255.255.0
permit = 192.168.0.0/255.255.0.0
permit = 10.0.0.0/255.0.0.0
permit = 172.16.0.0/255.240.0.0
read = system,call,log,verbose,command,agent,user,config,dtmf,reporting,cdr,dialplan,originate
write = system,call,log,verbose,command,agent,user,config,dtmf,reporting,cdr,dialplan,originate
writetimeout = 5000
EOF

# Configure Queues
sudo tee /etc/asterisk/queues.conf > /dev/null <<EOF
[general]
persistentmembers = yes
autofill = yes
monitor-type = MixMonitor
updatecdr = yes

[predictive-queue]
strategy = rrmemory
timeout = 30
retry = 5
maxlen = 0
announce-frequency = 0
announce-holdtime = no
announce-position = no
joinempty = yes
leavewhenempty = no
ringinuse = no
wrapuptime = 5
autopause = no
autopausedelay = 0
setinterfacevar = yes
setqueueentryvar = yes
setqueuevar = yes

; Add agents dynamically via AMI
; member => PJSIP/agent01
; member => PJSIP/agent02
EOF

# Set permissions
sudo chown -R asterisk:asterisk /etc/asterisk/

# Restart Asterisk
echo "🔄 Restarting Asterisk..."
sudo systemctl restart asterisk

# Check status
echo "📊 Checking Asterisk status..."
sudo systemctl status asterisk --no-pager

echo ""
echo "✅ Production Asterisk setup completed!"
echo ""
echo "📋 Configuration Summary:"
echo "  - SIP Trunk: 49.128.184.138"
echo "  - Username: mnd"
echo "  - Prefix: 99864"
echo "  - AMI Port: 5038"
echo "  - Agent Extensions: agent01-agent10"
echo ""
echo "🔧 Next Steps:"
echo "  1. Update your public IP in pjsip.conf"
echo "  2. Test SIP registration: asterisk -r -> pjsip show registrations"
echo "  3. Test AMI connection: telnet localhost 5038"
echo "  4. Configure agent SIP clients"
echo ""
echo "📱 Agent SIP Settings:"
echo "  - Server: YOUR_SERVER_IP"
echo "  - Port: 5060"
echo "  - Username: agent01-agent10"
echo "  - Password: agent01pass-agent10pass"
EOF

chmod +x setup-asterisk-production.sh
#!/bin/bash

# Production Asterisk Setup Script for Predictive Dialer
echo "🚀 Setting up Asterisk for Production Predictive Dialer..."

# Backup existing configs
sudo cp /etc/asterisk/pjsip.conf /etc/asterisk/pjsip.conf.backup
sudo cp /etc/asterisk/extensions.conf /etc/asterisk/extensions.conf.backup
sudo cp /etc/asterisk/manager.conf /etc/asterisk/manager.conf.backup

# Configure PJSIP for production trunk
sudo tee /etc/asterisk/pjsip.conf > /dev/null <<EOF
[global]
type=global
user_agent=Asterisk-PredictiveDialer
allow_guest=no
debug=yes

[transport-udp]
type=transport
protocol=udp
bind=0.0.0.0:5060
external_media_address=YOUR_PUBLIC_IP
external_signaling_address=YOUR_PUBLIC_IP

; ======================
; SIP TRUNK CONFIGURATION
; ======================
[trunk-mnd]
type=registration
outbound_auth=trunk-mnd-auth
server_uri=sip:49.128.184.138
client_uri=sip:mnd@49.128.184.138
retry_interval=60
forbidden_retry_interval=600
expiration=3600
transport=transport-udp

[trunk-mnd-auth]
type=auth
auth_type=userpass
username=mnd
password=15\$2g238!2dh3vHF2d33d

[trunk-mnd-aor]
type=aor
contact=sip:49.128.184.138

[trunk-mnd-endpoint]
type=endpoint
transport=transport-udp
context=from-trunk
disallow=all
allow=ulaw
allow=alaw
allow=g729
outbound_auth=trunk-mnd-auth
aors=trunk-mnd-aor
from_user=mnd
from_domain=49.128.184.138
t38_udptl=no
rtp_symmetric=yes
rewrite_contact=yes
force_rport=yes
direct_media=no

[trunk-mnd-identify]
type=identify
endpoint=trunk-mnd-endpoint
match=49.128.184.138

; ======================
; AGENT ENDPOINTS (101-120)
; ======================
[agent-template](!)
type=endpoint
context=agents
disallow=all
allow=ulaw
allow=alaw
transport=transport-udp
direct_media=no
rtp_symmetric=yes
force_rport=yes

[agent-auth-template](!)
type=auth
auth_type=userpass

[agent-aor-template](!)
type=aor
max_contacts=1
qualify_frequency=30
remove_existing=yes

; Agent 101-120
[agent01](agent-template)
auth=agent01-auth
aors=agent01

[agent01-auth](agent-auth-template)
username=agent01
password=agent01pass

[agent01](agent-aor-template)

[agent02](agent-template)
auth=agent02-auth
aors=agent02

[agent02-auth](agent-auth-template)
username=agent02
password=agent02pass

[agent02](agent-aor-template)

[agent03](agent-template)
auth=agent03-auth
aors=agent03

[agent03-auth](agent-auth-template)
username=agent03
password=agent03pass

[agent03](agent-aor-template)

[agent04](agent-template)
auth=agent04-auth
aors=agent04

[agent04-auth](agent-auth-template)
username=agent04
password=agent04pass

[agent04](agent-aor-template)

[agent05](agent-template)
auth=agent05-auth
aors=agent05

[agent05-auth](agent-auth-template)
username=agent05
password=agent05pass

[agent05](agent-aor-template)

[agent06](agent-template)
auth=agent06-auth
aors=agent06

[agent06-auth](agent-auth-template)
username=agent06
password=agent06pass

[agent06](agent-aor-template)

[agent07](agent-template)
auth=agent07-auth
aors=agent07

[agent07-auth](agent-auth-template)
username=agent07
password=agent07pass

[agent07](agent-aor-template)

[agent08](agent-template)
auth=agent08-auth
aors=agent08

[agent08-auth](agent-auth-template)
username=agent08
password=agent08pass

[agent08](agent-aor-template)

[agent09](agent-template)
auth=agent09-auth
aors=agent09

[agent09-auth](agent-auth-template)
username=agent09
password=agent09pass

[agent09](agent-aor-template)

[agent10](agent-template)
auth=agent10-auth
aors=agent10

[agent10-auth](agent-auth-template)
username=agent10
password=agent10pass

[agent10](agent-aor-template)
EOF

# Configure Extensions for Predictive Dialer
sudo tee /etc/asterisk/extensions.conf > /dev/null <<EOF
[general]
static=yes
writeprotect=no
clearglobalvars=no

[globals]
TRUNK_PREFIX=99864
TRUNK_ENDPOINT=trunk-mnd-endpoint

[agents]
; Agent internal calls
exten => _agent0[1-9],1,Dial(PJSIP/\${EXTEN},20)
exten => _agent0[1-9],n,Voicemail(\${EXTEN}@default,u)
exten => _agent0[1-9],n,Hangup()

exten => _agent1[0-9],1,Dial(PJSIP/\${EXTEN},20)
exten => _agent1[0-9],n,Voicemail(\${EXTEN}@default,u)
exten => _agent1[0-9],n,Hangup()

[from-internal]
; Internal agent to agent calls
exten => _agent0[1-9],1,Dial(PJSIP/\${EXTEN},20)
exten => _agent0[1-9],n,Hangup()

exten => _agent1[0-9],1,Dial(PJSIP/\${EXTEN},20)
exten => _agent1[0-9],n,Hangup()

; Manual outbound calls (for testing)
exten => _X.,1,Set(CALLERID(num)=\${TRUNK_PREFIX})
exten => _X.,n,Dial(PJSIP/\${TRUNK_PREFIX}\${EXTEN}@\${TRUNK_ENDPOINT},60)
exten => _X.,n,Hangup()

[predictive-dialer]
; Main predictive dialer context
exten => s,1,NoOp(=== PREDICTIVE DIALER CALL START ===)
exten => s,n,Set(CALL_ID=\${CALL_ID})
exten => s,n,Set(CAMPAIGN_ID=\${CAMPAIGN_ID})
exten => s,n,Set(CUSTOMER_NAME=\${CUSTOMER_NAME})
exten => s,n,Set(CUSTOMER_PHONE=\${CUSTOMER_PHONE})
exten => s,n,Set(CALLERID(num)=\${TRUNK_PREFIX})
exten => s,n,NoOp(Calling customer: \${CUSTOMER_PHONE})

; Make outbound call with prefix
exten => s,n,Dial(PJSIP/\${TRUNK_PREFIX}\${CUSTOMER_PHONE}@\${TRUNK_ENDPOINT},30,gM(predictive-answered))
exten => s,n,Goto(s-\${DIALSTATUS},1)

; Handle different call statuses
exten => s-ANSWER,1,NoOp(Customer answered)
exten => s-ANSWER,n,Set(CDR(userfield)=ANSWERED)
exten => s-ANSWER,n,Queue(predictive-queue,t,,,300)
exten => s-ANSWER,n,Hangup()

exten => s-BUSY,1,NoOp(Customer busy)
exten => s-BUSY,n,Set(CDR(userfield)=BUSY)
exten => s-BUSY,n,Hangup()

exten => s-NOANSWER,1,NoOp(Customer no answer)
exten => s-NOANSWER,n,Set(CDR(userfield)=NOANSWER)
exten => s-NOANSWER,n,Hangup()

exten => s-CONGESTION,1,NoOp(Network congestion)
exten => s-CONGESTION,n,Set(CDR(userfield)=FAILED)
exten => s-CONGESTION,n,Hangup()

exten => s-CHANUNAVAIL,1,NoOp(Channel unavailable)
exten => s-CHANUNAVAIL,n,Set(CDR(userfield)=FAILED)
exten => s-CHANUNAVAIL,n,Hangup()

exten => _s-.,1,NoOp(Other status: \${EXTEN})
exten => _s-.,n,Set(CDR(userfield)=FAILED)
exten => _s-.,n,Hangup()

; Macro for answered calls
[macro-predictive-answered]
exten => s,1,NoOp(Customer answered, connecting to agent)
exten => s,n,Set(CDR(userfield)=ANSWERED)
exten => s,n,Return()

[predictive-queue-context]
; Context for queue operations
exten => s,1,NoOp(Entering predictive queue)
exten => s,n,Queue(predictive-queue,t,,,300)
exten => s,n,Hangup()

[from-trunk]
; Incoming calls from trunk
exten => _X.,1,NoOp(Incoming call from trunk: \${EXTEN})
exten => _X.,n,Dial(PJSIP/agent01,20)
exten => _X.,n,Voicemail(agent01@default,u)
exten => _X.,n,Hangup()

[default]
include => agents
include => from-internal
EOF

# Configure Manager for AMI
sudo tee /etc/asterisk/manager.conf > /dev/null <<EOF
[general]
enabled = yes
port = 5038
bindaddr = 0.0.0.0
displayconnects = yes
timestampevents = yes

[admin]
secret = amp111
deny = 0.0.0.0/0.0.0.0
permit = 127.0.0.1/255.255.255.0
permit = 192.168.0.0/255.255.0.0
permit = 10.0.0.0/255.0.0.0
permit = 172.16.0.0/255.240.0.0
read = system,call,log,verbose,command,agent,user,config,dtmf,reporting,cdr,dialplan,originate
write = system,call,log,verbose,command,agent,user,config,dtmf,reporting,cdr,dialplan,originate
writetimeout = 5000
EOF

# Configure Queues
sudo tee /etc/asterisk/queues.conf > /dev/null <<EOF
[general]
persistentmembers = yes
autofill = yes
monitor-type = MixMonitor
updatecdr = yes

[predictive-queue]
strategy = rrmemory
timeout = 30
retry = 5
maxlen = 0
announce-frequency = 0
announce-holdtime = no
announce-position = no
joinempty = yes
leavewhenempty = no
ringinuse = no
wrapuptime = 5
autopause = no
autopausedelay = 0
setinterfacevar = yes
setqueueentryvar = yes
setqueuevar = yes

; Add agents dynamically via AMI
; member => PJSIP/agent01
; member => PJSIP/agent02
EOF

# Set permissions
sudo chown -R asterisk:asterisk /etc/asterisk/

# Restart Asterisk
echo "🔄 Restarting Asterisk..."
sudo systemctl restart asterisk

# Check status
echo "📊 Checking Asterisk status..."
sudo systemctl status asterisk --no-pager

echo ""
echo "✅ Production Asterisk setup completed!"
echo ""
echo "📋 Configuration Summary:"
echo "  - SIP Trunk: 49.128.184.138"
echo "  - Username: mnd"
echo "  - Prefix: 99864"
echo "  - AMI Port: 5038"
echo "  - Agent Extensions: agent01-agent10"
echo ""
echo "🔧 Next Steps:"
echo "  1. Update your public IP in pjsip.conf"
echo "  2. Test SIP registration: asterisk -r -> pjsip show registrations"
echo "  3. Test AMI connection: telnet localhost 5038"
echo "  4. Configure agent SIP clients"
echo ""
echo "📱 Agent SIP Settings:"
echo "  - Server: YOUR_SERVER_IP"
echo "  - Port: 5060"
echo "  - Username: agent01-agent10"
echo "  - Password: agent01pass-agent10pass"
EOF

chmod +x setup-asterisk-production.sh
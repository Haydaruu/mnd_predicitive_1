# Predictive Dialer Setup Guide

## Konfigurasi 2 Laptop

### Laptop 1 (Laravel Application)
- IP: 192.168.1.50 (contoh)
- Menjalankan aplikasi Laravel dengan Inertia React
- Terhubung ke Asterisk melalui AMI

### Laptop 2 (Asterisk PBX)
- IP: 192.168.1.100 (contoh)
- Menjalankan Asterisk PBX
- Konfigurasi AMI untuk menerima koneksi dari Laravel

## Setup Asterisk PBX (Laptop 2)

### 1. Install Asterisk
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install asterisk

# CentOS/RHEL
sudo yum install asterisk
```

### 2. Konfigurasi AMI (/etc/asterisk/manager.conf)
```ini
[general]
enabled = yes
port = 5038
bindaddr = 0.0.0.0

[admin]
secret = amp111
deny = 0.0.0.0/0.0.0.0
permit = 192.168.1.50/255.255.255.0
read = system,call,log,verbose,command,agent,user,config,command,dtmf,reporting,cdr,dialplan
write = system,call,log,verbose,command,agent,user,config,command,dtmf,reporting,cdr,dialplan
```

### 3. Konfigurasi SIP (/etc/asterisk/sip.conf)
```ini
[general]
context=default
allowoverlap=no
bindport=5060
bindaddr=0.0.0.0
srvlookup=yes
disallow=all
allow=ulaw
allow=alaw
allow=gsm

[trunk]
type=friend
host=dynamic
context=from-trunk
disallow=all
allow=ulaw
allow=alaw

; Agent Extensions
[101]
type=friend
secret=password123
host=dynamic
context=from-internal
disallow=all
allow=ulaw
allow=alaw

[102]
type=friend
secret=password123
host=dynamic
context=from-internal
disallow=all
allow=ulaw
allow=alaw
```

### 4. Konfigurasi Dialplan (/etc/asterisk/extensions.conf)
```ini
[general]
static=yes
writeprotect=no

[from-internal]
exten => _X.,1,Dial(SIP/trunk/${EXTEN})
exten => _X.,n,Hangup()

[predictive-dialer]
exten => _X.,1,NoOp(Predictive Dialer Call)
exten => _X.,n,Set(CALL_ID=${CALL_ID})
exten => _X.,n,Set(CAMPAIGN_ID=${CAMPAIGN_ID})
exten => _X.,n,Set(CUSTOMER_NAME=${CUSTOMER_NAME})
exten => _X.,n,Set(CUSTOMER_PHONE=${CUSTOMER_PHONE})
exten => _X.,n,Set(AGENT_ID=${AGENT_ID})
exten => _X.,n,Dial(SIP/${EXTEN},30)
exten => _X.,n,Hangup()

[from-trunk]
exten => _X.,1,Dial(SIP/${EXTEN})
exten => _X.,n,Hangup()
```

### 5. Restart Asterisk
```bash
sudo systemctl restart asterisk
sudo systemctl enable asterisk
```

## Setup Laravel Application (Laptop 1)

### 1. Update .env file
```env
ASTERISK_AMI_HOST=192.168.1.100
ASTERISK_AMI_PORT=5038
ASTERISK_AMI_USERNAME=admin
ASTERISK_AMI_SECRET=amp111
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Run Migrations
```bash
php artisan migrate
php artisan db:seed
```

### 4. Start Services
```bash
# Terminal 1: Laravel Server
php artisan serve --host=0.0.0.0 --port=8000

# Terminal 2: Queue Worker
php artisan queue:work

# Terminal 3: Frontend
npm run dev
```

## Testing Connection

### 1. Test AMI Connection
```bash
# From Laravel laptop
telnet 192.168.1.100 5038
```

### 2. Test SIP Registration
```bash
# From Asterisk console
asterisk -r
sip show peers
```

## Fitur Excel Export

### 1. Call Reports Export
- URL: `/reports/export/calls`
- Format: Excel dengan multiple sheets
- Filter: Campaign, Agent, Date Range

### 2. Campaign Summary Export
- URL: `/reports/export/campaign/{id}`
- Format: Excel dengan 3 sheets:
  - Campaign Summary
  - Call Details
  - Agent Performance

## Network Configuration

### Firewall Rules (Laptop 2 - Asterisk)
```bash
# Allow AMI port
sudo ufw allow 5038

# Allow SIP port
sudo ufw allow 5060

# Allow RTP ports
sudo ufw allow 10000:20000/udp
```

### Network Testing
```bash
# Test connectivity
ping 192.168.1.100

# Test AMI port
telnet 192.168.1.100 5038

# Test SIP port
telnet 192.168.1.100 5060
```

## Troubleshooting

### 1. AMI Connection Issues
- Check firewall settings
- Verify IP addresses in manager.conf
- Check Asterisk logs: `/var/log/asterisk/messages`

### 2. Call Issues
- Check SIP registration
- Verify trunk configuration
- Monitor Asterisk console: `asterisk -r`

### 3. Laravel Issues
- Check queue worker is running
- Monitor Laravel logs: `storage/logs/laravel.log`
- Verify database connections

## Monitoring

### Asterisk Console Commands
```bash
asterisk -r
sip show peers
core show channels
manager show connected
```

### Laravel Monitoring
```bash
# Monitor queue
php artisan queue:monitor

# Check logs
tail -f storage/logs/laravel.log
```
# OLT Telnet/SSH Access — নেটওয়ার্ক টিমের জন্য

## OLT তথ্য
- **OLT IP**: 103.85.182.17
- **OLT Model**: BDCOM GP3600-08D (GPON OLT-3)
- **VPS IP (allow করতে হবে)**: 139.59.107.78

## কেন দরকার
বর্তমানে SNMP দিয়ে ONU online/offline status পাওয়া যাচ্ছে।
কিন্তু per-ONU RX/TX optical power পেতে CLI (Telnet/SSH) লাগবে।

Command যেগুলো চালাতে হয়:
  show gpon onu optical-info gpon-onu_1/1  (প্রতিটি port-এর জন্য)
  show gpon onu state gpon-onu_1/1

## OLT-এ যা করতে হবে (network engineer)

### SSH দিয়ে (recommended):


### Telnet দিয়ে (alternative):


### ACL/Firewall থাকলে:


## Test করার পদ্ধতি
VPS-এ SSH করে:


## Access দেওয়ার পর জানান
Access দেওয়ার পর ISP billing system automatically per-ONU RX/TX power collect করা শুরু করবে।
কোনো code change লাগবে না।

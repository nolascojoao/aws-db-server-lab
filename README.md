# AWS Database Creation *(IN PROGRESS)*

<div align="center">
  <img src="screenshot/architecture-lab2.jpg" width=""/>
</div>

---

# Objectives

- Launch an Amazon RDS DB instance with high availability.
- Configure the DB instance to permit connections from web server.
- Open a web application and interact with database.

---

## Step 1: VPC Creation
#### 1.1. Create a VPC with CIDR block 10.0.0.0/16:
```bash
aws ec2 create-vpc --cidr-block 10.0.0.0/16 \
  --tag-specifications 'ResourceType=vpc,Tags=[{Key=Name,Value=my-vpc}]'
```

---

## Step 2: Create Subnets
#### 2.1. Create Public Subnet 1 in Availability Zone A with CIDR block 10.0.0.0/24:
```bash
aws ec2 create-subnet --vpc-id <vpc-id> --cidr-block 10.0.0.0/24 --availability-zone <az-a> \
  --tag-specifications 'ResourceType=subnet,Tags=[{Key=Name,Value=public-subnet-1}]'
```
#### 2.2. Create Private Subnet 1 in Availability Zone A with CIDR block 10.0.1.0/24:
```bash
aws ec2 create-subnet --vpc-id <vpc-id> --cidr-block 10.0.1.0/24 --availability-zone <az-a> \
  --tag-specifications 'ResourceType=subnet,Tags=[{Key=Name,Value=private-subnet-1}]'
```
#### 2.3. Create Public Subnet 2 in Availability Zone B with CIDR block 10.0.2.0/24:
```bash
aws ec2 create-subnet --vpc-id <vpc-id> --cidr-block 10.0.2.0/24 --availability-zone <az-b> \
  --tag-specifications 'ResourceType=subnet,Tags=[{Key=Name,Value=public-subnet-2}]'
```
#### 2.4. Create Private Subnet 2 in Availability Zone B with CIDR block 10.0.3.0/24:
```bash
aws ec2 create-subnet --vpc-id <vpc-id> --cidr-block 10.0.3.0/24 --availability-zone <az-b> \
  --tag-specifications 'ResourceType=subnet,Tags=[{Key=Name,Value=private-subnet-2}]'
```

---

## Step 3: Create an Internet Gateway and Attach to VPC
#### 3.1. Create an internet gateway:
```bash
aws ec2 create-internet-gateway \
  --tag-specifications 'ResourceType=internet-gateway,Tags=[{Key=Name,Value=my-igw}]'
```
#### 3.2. Attach the internet gateway to the VPC:
```bash
aws ec2 attach-internet-gateway --vpc-id <vpc-id> --internet-gateway-id <igw-id>
```

---

## Step 4: Set up NAT Gateway in Public Subnet 1
#### 4.1. Allocate an Elastic IP address for the NAT Gateway:
```bash
aws ec2 allocate-address --domain vpc
```
#### 4.2. Create the NAT Gateway in Public Subnet 1:
```bash
aws ec2 create-nat-gateway \
  --subnet-id <public-subnet-1-id> --allocation-id <elastic-ip-allocation-id>
```

---

## Step 5: Create Route Tables and Associate with Subnets
#### 5.1. Create a route table for public subnets and associate with Public Subnet 1 and Public Subnet 2:
```bash
aws ec2 create-route-table --vpc-id <vpc-id> \
  --tag-specifications 'ResourceType=route-table,Tags=[{Key=Name,Value=public-route-table}]'
```
#### 5.2. Create a route to the internet through the internet gateway:
```bash
aws ec2 create-route --route-table-id <public-route-table-id> --destination-cidr-block 0.0.0.0/0 \
  --gateway-id <igw-id>
```
#### 5.3. Associate the route table with Public Subnet 1 and Public Subnet 2:
```bash
aws ec2 associate-route-table --route-table-id <public-route-table-id> --subnet-id <public-subnet-1-id>
aws ec2 associate-route-table --route-table-id <public-route-table-id> --subnet-id <public-subnet-2-id>
```
#### 5.4. Create a route table for private subnets and associate with Private Subnet 1 and Private Subnet 2:
```bash
aws ec2 create-route-table --vpc-id <vpc-id> \
  --tag-specifications 'ResourceType=route-table,Tags=[{Key=Name,Value=private-route-table}]'
```
#### 5.5. Create a route to the NAT Gateway for internet access from private subnets:
```bash
aws ec2 create-route --route-table-id <private-route-table-id> --destination-cidr-block 0.0.0.0/0 \
  --nat-gateway-id <nat-gateway-id>
```
#### 5.6. Associate the private route table with Private Subnet 1 and Private Subnet 2:
```bash
aws ec2 associate-route-table --route-table-id <private-route-table-id> --subnet-id <private-subnet-1-id>
aws ec2 associate-route-table --route-table-id <private-route-table-id> --subnet-id <private-subnet-2-id>
```

---

## Step 6: Set Up RDS Primary and Secondary in Private Subnets
#### 6.1. Create an RDS instance in Private Subnet 1 (primary) and Private Subnet 2 (secondary) with Multi-AZ enabled:
```bash
aws rds create-db-instance \
  --db-instance-identifier mydbinstance \
  --db-instance-class db.t3.micro \
  --engine mysql \
  --master-username <username> --master-user-password <password> \
  --allocated-storage 20 \
  --vpc-security-group-ids <rds-sg-id> \
  --db-subnet-group-name <subnet-group-name> \
  --multi-az --availability-zone <az-a> \
  --backup-retention-period 7
```
#### 6.2. Confirm the creation of the RDS instance by describing it:
```bash
aws rds describe-db-instances --db-instance-identifier mydbinstance
```

---

## Step 7: Launch Web Server in Public Subnet 2
#### 7.1. Launch an EC2 instance with an Amazon Linux 2023 AMI in Public Subnet 2 with a security group that allows HTTP traffic (port 80):
```bash
aws ec2 run-instances \
  --image-id ami-0ebfd941bbafe70c6 \
  --instance-type t2.micro \
  --key-name <key-pair-name> \
  --security-group-ids <web-sg-id> \
  --subnet-id <public-subnet-2-id> \
  --associate-public-ip-address \
  --user-data <file://install-web-server.sh>
```

---
#### Important ⚠️
**Before launching EC2**

Edit `install-web-server.sh` with your RDS credentials:
```php
$servername = "your_rds_endpoint"; // Replace with your RDS endpoint
$username = "your_username"; // Replace with your RDS username
$password = "your_password"; // Replace with your RDS password
```

---

## Step 8: Clean Up Resources (Optional)
#### 8.1. Terminate the EC2 instance:
```bash
aws ec2 terminate-instances --instance-ids <instance-id>
```
#### 8.2. Delete the RDS instance:
```bash
aws rds delete-db-instance --db-instance-identifier mydbinstance --skip-final-snapshot
```
#### 8.3. Delete the NAT gateway:
```bash
aws ec2 delete-nat-gateway --nat-gateway-id <nat-gateway-id>
```
#### 8.4. Delete the subnets, route tables, and VPC:
```bash
aws ec2 delete-subnet --subnet-id <subnet-id>
aws ec2 delete-route-table --route-table-id <route-table-id>
aws ec2 delete-vpc --vpc-id <vpc-id>
```

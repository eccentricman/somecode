import torch
import torch.nn as nn
import torch.optim as optim
import torchvision
import torchvision.transforms as transforms
from torch.utils.data import DataLoader
from NET.net1 import SlimNet
# 图像的预处理：缩放到 64x64 大小，并归一化
# 这个变换管道通常用于图像分类任务中的数据预处理步骤。通过调整图像大小、转换为张量和标准化，可以确保输入数据的一致性和模型训练的稳定性。
transform = transforms.Compose([
    transforms.Resize((64, 64)),
    #将图像从 PIL 图像或 numpy 数组转换为 PyTorch 张量，并且会自动将图像的像素值从 [0, 255] 范围缩放到 [0, 1] 范围。
    transforms.ToTensor(),
    #使用给定的均值和标准差对图像进行标准化。这里的均值和标准差分别是 [0.5, 0.5, 0.5]，表示对每个通道（RGB）进行标准化。标准化公式为： [ text{output} = frac{text{input} - text{mean}}{text{std}} ] 由于输入的像素值已经被 transforms.ToTensor 缩放到 [0, 1] 范围，标准化后的像素值将被调整到 [-1, 1] 范围。
    transforms.Normalize(mean=[0.5, 0.5, 0.5], std=[0.5, 0.5, 0.5])
])

# 加载 CelebA 数据集,如果google drive可以用，download=True，可以直接下载，也可以自己下载后解压到/root/celeba目录下
root = './data'
train_dataset = torchvision.datasets.CelebA(root=root, split='train', download=False, transform=transform)
test_dataset = torchvision.datasets.CelebA(root=root, split='test', download=False, transform=transform)

# 创建数据加载器，改大batch_size，在GPU T4下显存使用率没增加？
batch_size = 16
train_loader = DataLoader(dataset=train_dataset, batch_size=batch_size, shuffle=True)
test_loader = DataLoader(dataset=test_dataset, batch_size=batch_size, shuffle=False)

# 检查数据加载是否成功
print(f"Number of training samples: {len(train_dataset)}")
print(f"Number of testing samples: {len(test_dataset)}")
class SimpleCNN(nn.Module):
    def __init__(self):
        super(SimpleCNN, self).__init__()
        self.conv1 = nn.Conv2d(in_channels=3, out_channels=64, kernel_size=3, stride=1, padding=1)
        self.conv2 = nn.Conv2d(in_channels=64, out_channels=128, kernel_size=3, stride=1, padding=1)
        self.conv3 = nn.Conv2d(in_channels=128, out_channels=256, kernel_size=3, stride=1, padding=1)
        self.fc1 = nn.Linear(256*8*8, 512)
        self.fc2 = nn.Linear(512, 40)  # CelebA 有 40 个属性标签

        self.pool = nn.MaxPool2d(kernel_size=2, stride=2)
        self.relu = nn.ReLU()

    def forward(self, x):
        x = self.relu(self.conv1(x))
        x = self.pool(x)
        x = self.relu(self.conv2(x))
        x = self.pool(x)
        x = self.relu(self.conv3(x))
        x = self.pool(x)
        
        x = x.view(x.size(0), -1)  # 展平操作
        x = self.relu(self.fc1(x))
        x = self.fc2(x)
        
        return x

# 实例化模型
model = SimpleCNN()
    # 检查 CUDA 是否可用，并选择设备
device = torch.device("cuda" if torch.cuda.is_available() else "cpu")

#model=torch.load('model_77.33130447850917.pt')
model = model.to(device)
criterion = nn.BCEWithLogitsLoss()  # 二分类交叉熵损失
optimizer = optim.Adam(model.parameters(), lr=0.001)
def train(model, train_loader, criterion, optimizer, num_epochs, device):
    model.train() #设置模型为训练模式
    for epoch in range(num_epochs):
        for images, labels in train_loader:
            # 将数据移动到选定的设备
            images, labels = images.to(device), labels.to(device).float()
            
            # 前向传播
            outputs = model(images)
            loss = criterion(outputs, labels)
            
            # 反向传播和优化
            optimizer.zero_grad()#清零梯度
            loss.backward()#计算梯度
            optimizer.step()#更新参数
        
        print(f'Epoch [{epoch+1}/{num_epochs}], Loss: {loss.item():.4f}')
                
def evaluate(model, test_loader, criterion, device):
    model.eval() # 设置模型为评估模式
    with torch.no_grad(): # 禁用梯度计算
        total_loss = 0
        correct = 0
        total = 0
        for images, labels in test_loader:
            # 将数据移动到选定的设备
            images, labels = images.to(device), labels.to(device).float()
            
            # 前向传播
            outputs = model(images)
            # 计算损失，并累加到 total_loss
            loss = criterion(outputs, labels)
            total_loss += loss.item()
            
            # 计算准确率
            predicted = (outputs > 0.5).float()
            total += labels.size(0) * labels.size(1)  # 总标签数
            correct += (predicted == labels).sum().item()
        # 打印平均损失和准确率
        print(f'Average loss: {total_loss / len(test_loader):.4f}, Accuracy: {100 * correct / total:.2f}%')
# 检查 CUDA 是否可用，并选择设备
device = torch.device("cuda" if torch.cuda.is_available() else "cpu")

# 将模型移动到选定的设备
model = model.to(device)

# 训练模型
train(model, train_loader, criterion, optimizer, num_epochs=10, device=device)

# 验证模型
evaluate(model, test_loader, criterion, device=device)
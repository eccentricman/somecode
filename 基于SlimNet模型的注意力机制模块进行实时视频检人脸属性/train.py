import torch
import torch.nn as nn
import time
import torchvision
import torchvision.transforms as transforms
from torch.utils.data import DataLoader
from NET.net1 import SlimNet
import torchvision.transforms.functional as TF

def adjust_brightness(img, brightness_factor):
    return TF.adjust_brightness(img, brightness_factor)
  # 实例化模型
model = SlimNet(40)
    # 检查 CUDA 是否可用，并选择设备
device = torch.device("cuda" if torch.cuda.is_available() else "cpu")

#model=torch.load('model_layer4_77.34082256286945.pt')
model = model.to(device)
loss_criterion = nn.BCEWithLogitsLoss() #定义损失函数
optimizer = torch.optim.Adam(model.parameters(), lr = 0.00001) #定义优化器
# 图像的预处理：缩放到 64x64 大小，并归一化
    # 这个变换管道通常用于图像分类任务中的数据预处理步骤。通过调整图像大小、转换为张量和标准化，可以确保输入数据的一致性和模型训练的稳定性。
transform = transforms.Compose([
#            transforms.RandomAffine(45, shear=10, translate=(0.4, 0.4)),  # 随机仿射变换（包括平移和剪切）
            transforms.Resize((128, 128)),
            transforms.ToTensor(),
            transforms.Normalize(mean=[0.5, 0.5, 0.5], std=[0.5, 0.5, 0.5]),
            ])
#transform1 = transforms.Compose([
#            transforms.Lambda(lambda img: adjust_brightness(img, 10)),  # 降低亮度到50%
#            transforms.Resize((128, 128)),
#            transforms.ToTensor(),
#            transforms.Normalize(mean=[0.5, 0.5, 0.5], std=[0.5, 0.5, 0.5]),
#            ])
for epoch in range(1): #训练轮
    # 加载 CelebA 数据集
    root = './data'
    train_dataset = torchvision.datasets.CelebA(root=root, split='train', download=False, transform=transform )
    test_dataset = torchvision.datasets.CelebA(root=root, split='test', download=False, transform=transform1)
    # 创建数据加载器
    batch_size = 64
    train_dataloader = DataLoader(dataset=train_dataset, batch_size=batch_size, shuffle=True)
    test_dataloader = DataLoader(dataset=test_dataset, batch_size=batch_size, shuffle=False)
    seed = 1820386125270 #固定起始种子
    start_time = time.time()
    torch.manual_seed(seed)
    torch.cuda.manual_seed(seed)
    torch.backends.cudnn.benchmark = False
    torch.backends.cudnn.deterministic = True
    total_train = 0 #总共的训练图片数量，用来计算准确率
    correct_train = 0 #模型分类对的训练图片
    running_loss = 0 #训练集上的loss
    running_test_loss = 0 #测试集上的loss
    total_test = 0 #测试的图片总数
    correct_test = 0 #分类对的测试图片数
    model.train() #训练模式
    for data, target in train_dataloader:
        data = data.to(device=device)
        target = target.type(torch.DoubleTensor).to(device=device)
        score = model(data)
        loss = loss_criterion(score, target)
        running_loss += loss.item()
        optimizer.zero_grad()
        loss.backward()
        optimizer.step()
        predictions = (target > 0.5).float()  # 应用阈值得到最终的预测标签
        total_train += target.size(0) * target.size(1)
        correct_train += (target.type(predictions.type()) == predictions).sum().item()
    model.eval() #测试模式
    with torch.no_grad():
         for batch_idx, (images,labels) in enumerate(test_dataloader):
            images, labels = images.to(device), labels.type(torch.DoubleTensor).to(device)
            logits = model(images)
            test_loss = loss_criterion(logits, labels)
            running_test_loss += test_loss.item()
            predictions = (logits > 0.5).float()  # 应用阈值得到最终的预测标签
            total_test += labels.size(0) * labels.size(1)
            correct_test += (labels.type(predictions.type()) == predictions).sum().item()
    test_acc = correct_test/total_test
    end_time = time.time()
    print(f"For epoch : {epoch} time: {end_time-start_time}s")
    start_time = time.time()
    torch.save(model,f"model_{test_acc*100}.pt")
    print(f"For epoch : {epoch} training loss: {running_loss/len(train_dataloader)}")
    print(f'train accruacy is {correct_train*100/total_train}%')
    print(f"For epoch : {epoch} test loss: {running_test_loss/len(test_dataloader)}")
    print(f'test accruacy is {test_acc*100}%')



